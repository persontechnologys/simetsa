<?php

// tests/Feature/CancelacionTest.php

namespace Tests\Feature;

use App\Enums\EstadoReembolso;
use App\Enums\EstadoTicket;
use App\Enums\TipoCancelacion;
use App\Models\Cancelacion;
use App\Models\Conductor;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del módulo de Cancelaciones (Fase 9.5.3).
 *
 * Cubre:
 *  - Cancelación voluntaria del conductor via TicketService::cancelar.
 *  - Anulación administrativa via TicketService::anular.
 *  - Verificación del estado del ticket post-cancelación.
 *  - Autorización del backoffice (GET /cancelaciones).
 */
class CancelacionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $comisario;
    private User $director;
    private User $conductorUser;
    private Conductor $conductor;
    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
            ParametroSeeder::class,
        ]);

        $this->superAdmin    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->comisario     = User::where('email', 'comisario@simetsa.gob.ec')->first();
        $this->director      = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->conductorUser = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->conductor     = $this->crearConductor();
        $this->service       = app(TicketService::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function crearConductor(): Conductor
    {
        return Conductor::firstOrCreate(
            ['user_id' => $this->conductorUser->id],
            ['codigo' => 'CD-TEST1', 'estado' => Conductor::ESTADO_ACTIVO]
        );
    }

    private function crearTicketPendiente(): Ticket
    {
        return Ticket::factory()->create([
            'conductor_id' => $this->conductor->id,
            'estado'       => EstadoTicket::Pendiente,
        ]);
    }

    // ── Backoffice: autorización ──────────────────────────────────────────────

    /** Invitado es redirigido al login. */
    public function test_invitado_no_puede_acceder_al_listado(): void
    {
        $this->get(route('cancelaciones.index'))->assertRedirect('/login');
    }

    /** Conductor no tiene acceso al backoffice de cancelaciones. */
    public function test_conductor_no_puede_ver_listado(): void
    {
        $this->actingAs($this->conductorUser)
             ->get(route('cancelaciones.index'))
             ->assertForbidden();
    }

    /** Comisario puede ver el listado de cancelaciones. */
    public function test_comisario_puede_ver_listado(): void
    {
        $this->actingAs($this->comisario)
             ->get(route('cancelaciones.index'))
             ->assertOk()
             ->assertViewIs('cancelaciones.index')
             ->assertViewHas('cancelaciones');
    }

    /** Director puede ver el listado de cancelaciones. */
    public function test_director_puede_ver_listado(): void
    {
        $this->actingAs($this->director)
             ->get(route('cancelaciones.index'))
             ->assertOk();
    }

    /** Super admin puede ver el listado. */
    public function test_super_admin_puede_ver_listado(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('cancelaciones.index'))
             ->assertOk();
    }

    // ── Cancelación por conductor ─────────────────────────────────────────────

    /** El conductor puede cancelar un ticket en estado pendiente. */
    public function test_conductor_puede_cancelar_ticket_pendiente(): void
    {
        $ticket = $this->crearTicketPendiente();

        $cancelacion = $this->service->cancelar(
            $ticket,
            $this->conductorUser,
            'Ya no necesito el espacio.'
        );

        $this->assertInstanceOf(Cancelacion::class, $cancelacion);
        $this->assertEquals(TipoCancelacion::Conductor, $cancelacion->tipo);
        $this->assertEquals(EstadoTicket::Cancelado, $ticket->fresh()->estado);
    }

    /** Después de cancelar, el ticket queda en estado 'cancelado'. */
    public function test_ticket_queda_cancelado_tras_cancelacion_conductor(): void
    {
        $ticket = $this->crearTicketPendiente();
        $this->service->cancelar($ticket, $this->conductorUser, 'No necesito más el espacio.');

        $this->assertDatabaseHas('cancelaciones', [
            'ticket_id' => $ticket->id,
            'tipo'      => TipoCancelacion::Conductor->value,
        ]);
        $this->assertEquals(EstadoTicket::Cancelado, $ticket->fresh()->estado);
    }

    /** El reembolso de un ticket pagado en efectivo queda como 'no_aplica'. */
    public function test_reembolso_efectivo_es_no_aplica(): void
    {
        $ticket = $this->crearTicketPendiente();
        $cancelacion = $this->service->cancelar($ticket, $this->conductorUser, 'Motivo de prueba.');

        $this->assertEquals(EstadoReembolso::NoAplica, $cancelacion->estado_reembolso);
    }

    // ── Anulación administrativa ──────────────────────────────────────────────

    /** El comisario puede anular un ticket activo administrativamente. */
    public function test_comisario_puede_anular_ticket_activo(): void
    {
        $ticket = $this->crearTicketPendiente();
        $ticket->update(['estado' => EstadoTicket::Activo]);

        $cancelacion = $this->service->anular(
            $ticket,
            $this->comisario,
            'Vehículo infraccionado. Anulación administrativa.'
        );

        $this->assertInstanceOf(Cancelacion::class, $cancelacion);
        $this->assertEquals(TipoCancelacion::Admin, $cancelacion->tipo);
        $this->assertEquals(EstadoTicket::Anulado, $ticket->fresh()->estado);
    }

    /** La anulación aparece en el listado del backoffice con los filtros correctos. */
    public function test_cancelacion_admin_aparece_en_listado(): void
    {
        $ticket = $this->crearTicketPendiente();
        $ticket->update(['estado' => EstadoTicket::Activo]);
        $this->service->anular($ticket, $this->comisario, 'Motivo de anulación.');

        $this->actingAs($this->comisario)
             ->get(route('cancelaciones.index', ['tipo' => TipoCancelacion::Admin->value]))
             ->assertOk()
             ->assertViewHas('cancelaciones');
    }

    // ── Show ------------------------------------------------------------------

    /** Comisario puede ver el detalle de una cancelación. */
    public function test_comisario_puede_ver_detalle(): void
    {
        $ticket = $this->crearTicketPendiente();
        $cancelacion = $this->service->cancelar($ticket, $this->conductorUser, 'Prueba detalle.');

        $this->actingAs($this->comisario)
             ->get(route('cancelaciones.show', $cancelacion))
             ->assertOk()
             ->assertViewIs('cancelaciones.show')
             ->assertViewHas('cancelacion');
    }

    /** Conductor no puede acceder al detalle de una cancelación. */
    public function test_conductor_no_puede_ver_detalle(): void
    {
        $ticket = $this->crearTicketPendiente();
        $cancelacion = $this->service->cancelar($ticket, $this->conductorUser, 'Prueba.');

        $this->actingAs($this->conductorUser)
             ->get(route('cancelaciones.show', $cancelacion))
             ->assertForbidden();
    }
}
