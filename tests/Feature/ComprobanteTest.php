<?php

// tests/Feature/ComprobanteTest.php

namespace Tests\Feature;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoOrdenPago;
use App\Enums\EstadoTransaccion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Comprobante;
use App\Models\Conductor;
use App\Models\Infraccion;
use App\Models\OrdenPago;
use App\Models\Ticket;
use App\Models\TipoVehiculo;
use App\Models\TransaccionPago;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\TicketService;
use Carbon\Carbon;
use Database\Seeders\AgenteParqueoSeeder;
use Database\Seeders\HorarioOperacionSeeder;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests de Comprobantes y Órdenes de Pago — Fase 9.5.5.
 * Arts. 19, 28 — Ordenanza SIMETSA.
 */
class ComprobanteTest extends TestCase
{
    use RefreshDatabase;

    private const HORA_OPERATIVA = '2026-03-03 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            HorarioOperacionSeeder::class,
            ZonaSeeder::class,
            ParametroSeeder::class,
            AgenteParqueoSeeder::class,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    /** Crea un conductor modelo vinculado al conductorUser. */
    private function crearConductor(?User $user = null): Conductor
    {
        $user ??= $this->conductorUser();
        return Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-TMP-' . $user->id, 'estado' => Conductor::ESTADO_ACTIVO]
        );
    }

    /** Crea un vehiculo vinculado a un conductor. */
    private function crearVehiculo(Conductor $conductor): Vehiculo
    {
        $tipo = TipoVehiculo::factory()->create(['aplica_tarifa' => true]);
        return Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'TST' . str_pad($conductor->id, 4, '0', STR_PAD_LEFT),
            'estado'           => Vehiculo::ESTADO_ACTIVO,
        ]);
    }

    /** Crea un ticket Deuna y devuelve [ticket, transaccion]. */
    private function crearTicketConTransaccionDeuna(): array
    {
        Http::fake();
        Carbon::setTestNow(self::HORA_OPERATIVA);
        config(['pagos.deuna.enabled' => false]);

        $conductor = $this->crearConductor();
        $vehiculo  = $this->crearVehiculo($conductor);

        $zona = \App\Models\Zona::where('codigo', 'centro')->first();

        $service = app(TicketService::class);
        $ticket  = $service->comprar([
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $zona->id,
            'calle_id'        => null,
            'horas_compradas' => 1,
            'metodo_pago'     => 'link',
            'proveedor'       => 'deuna',
        ]);

        return [$ticket, $ticket->transacciones->first()];
    }

    /** Crea una infracción y una TransaccionPago pendiente asociada. */
    private function crearInfraccionConTransaccion(): array
    {
        $agente      = AgenteParqueo::where('codigo', 'AG-0001')->first();
        $infraccion  = Infraccion::factory()->create([
            'agente_parqueo_id' => $agente->id,
            'placa'             => 'MUL001',
            'tipo_infraccion'   => TipoInfraccion::SinAdquirirTicket,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => 30.00,
            'sbu_vigente'       => 460.00,
        ]);

        $transaccion = TransaccionPago::factory()->create([
            'concepto_type'      => Infraccion::class,
            'concepto_id'        => $infraccion->id,
            'proveedor'          => 'deuna',
            'monto'              => 30.00,
            'moneda'             => 'USD',
            'external_reference' => 'fake-inf-' . $infraccion->id,
            'estado'             => EstadoTransaccion::Pendiente,
        ]);

        return [$infraccion, $transaccion];
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    /**
     * Test 1: Webhook de pago genera comprobante automáticamente al acreditar un ticket.
     * Art. 19 — todo pago confirmado emite comprobante.
     */
    public function test_webhook_pago_genera_comprobante_al_acreditar_ticket(): void
    {
        [$ticket, $transaccion] = $this->crearTicketConTransaccionDeuna();

        $this->assertDatabaseMissing('comprobantes', ['concepto_type' => Ticket::class]);

        $this->postJson('/api/v1/pagos/webhook/deuna', [
            'external_reference' => $transaccion->external_reference,
            'status'             => 'approved',
        ])->assertOk();

        $this->assertDatabaseHas('comprobantes', [
            'concepto_type' => Ticket::class,
            'concepto_id'   => $ticket->id,
        ]);

        $comprobante = Comprobante::where('concepto_type', Ticket::class)
            ->where('concepto_id', $ticket->id)
            ->first();

        $this->assertNotNull($comprobante);
        $this->assertStringStartsWith('CB-', $comprobante->numero);
        $this->assertEquals((float) $ticket->monto, (float) $comprobante->monto);
    }

    /**
     * Test 2: Webhook de pago genera comprobante automáticamente al acreditar una infracción.
     * Art. 19 — aplica también al pago de multas (Art. 28).
     */
    public function test_webhook_pago_genera_comprobante_al_acreditar_infraccion(): void
    {
        config(['pagos.deuna.enabled' => false]);
        [$infraccion, $transaccion] = $this->crearInfraccionConTransaccion();

        $this->assertDatabaseMissing('comprobantes', ['concepto_type' => Infraccion::class]);

        $this->postJson('/api/v1/pagos/webhook/deuna', [
            'external_reference' => $transaccion->external_reference,
            'status'             => 'approved',
        ])->assertOk();

        $this->assertDatabaseHas('comprobantes', [
            'concepto_type' => Infraccion::class,
            'concepto_id'   => $infraccion->id,
        ]);
    }

    /**
     * Test 3: Conductor puede ver su propio comprobante.
     */
    public function test_conductor_puede_ver_su_propio_comprobante(): void
    {
        $conductorUser = $this->conductorUser();
        $conductor     = $this->crearConductor($conductorUser);
        $vehiculo      = $this->crearVehiculo($conductor);

        $ticket = Ticket::factory()->create([
            'conductor_id' => $conductor->id,
            'vehiculo_id'  => $vehiculo->id,
        ]);

        $comprobante = Comprobante::factory()->create([
            'concepto_type' => Ticket::class,
            'concepto_id'   => $ticket->id,
            'numero'        => 'CB-0001',
            'monto'         => $ticket->monto,
        ]);

        $this->withToken($this->token($conductorUser))
            ->getJson("/api/v1/comprobantes/{$comprobante->id}")
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.numero', 'CB-0001');
    }

    /**
     * Test 4: Conductor no puede ver el comprobante de otro conductor (403).
     */
    public function test_conductor_no_puede_ver_comprobante_de_otro_conductor(): void
    {
        // Conductor 1 (el dueño del ticket)
        $conductor1User = $this->conductorUser();
        $conductor1     = $this->crearConductor($conductor1User);
        $vehiculo       = $this->crearVehiculo($conductor1);

        $ticket = Ticket::factory()->create([
            'conductor_id' => $conductor1->id,
            'vehiculo_id'  => $vehiculo->id,
        ]);

        $comprobante = Comprobante::factory()->create([
            'concepto_type' => Ticket::class,
            'concepto_id'   => $ticket->id,
        ]);

        // Conductor 2 — distinto usuario con rol conductor
        $conductor2User = User::factory()->create();
        $conductor2User->assignRole('conductor');
        $this->crearConductor($conductor2User);

        $this->withToken($this->token($conductor2User))
            ->getJson("/api/v1/comprobantes/{$comprobante->id}")
            ->assertForbidden();
    }

    /**
     * Test 5: Comisario puede generar una orden de pago para una infracción.
     * Art. 28 — proceso formal de cobro de multa.
     */
    public function test_comisario_puede_generar_orden_de_pago(): void
    {
        $comisario  = $this->comisarioUser();
        $agente     = AgenteParqueo::where('codigo', 'AG-0001')->first();
        $infraccion = Infraccion::factory()->create([
            'agente_parqueo_id' => $agente->id,
            'placa'             => 'ORD001',
            'tipo_infraccion'   => TipoInfraccion::SinAdquirirTicket,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => 30.00,
            'sbu_vigente'       => 460.00,
        ]);

        $response = $this->withToken($this->token($comisario))
            ->postJson('/api/v1/ordenes-pago', [
                'infraccion_id' => $infraccion->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.estado', 'pendiente');

        $this->assertDatabaseHas('ordenes_pago', [
            'infraccion_id' => $infraccion->id,
            'estado'        => EstadoOrdenPago::Pendiente->value,
        ]);

        $orden = OrdenPago::where('infraccion_id', $infraccion->id)->first();
        $this->assertStringStartsWith('OP-', $orden->numero_orden);
    }

    /**
     * Test 6: No se puede generar una segunda orden de pago si ya existe una vigente.
     */
    public function test_no_se_puede_generar_segunda_orden_si_ya_existe_vigente(): void
    {
        $comisario  = $this->comisarioUser();
        $agente     = AgenteParqueo::where('codigo', 'AG-0001')->first();
        $infraccion = Infraccion::factory()->create([
            'agente_parqueo_id' => $agente->id,
            'placa'             => 'DUP001',
            'tipo_infraccion'   => TipoInfraccion::SinAdquirirTicket,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => 30.00,
            'sbu_vigente'       => 460.00,
        ]);

        // Primera orden
        $this->withToken($this->token($comisario))
            ->postJson('/api/v1/ordenes-pago', ['infraccion_id' => $infraccion->id])
            ->assertStatus(201);

        // Segunda orden — debe fallar
        $this->withToken($this->token($comisario))
            ->postJson('/api/v1/ordenes-pago', ['infraccion_id' => $infraccion->id])
            ->assertStatus(422)
            ->assertJsonPath('exito', false);
    }

    /**
     * Test 7: Comisario puede ver el listado de comprobantes en el backoffice.
     */
    public function test_comisario_puede_ver_listado_comprobantes_backoffice(): void
    {
        $comisario = $this->comisarioUser();

        // Crear algunos comprobantes
        Comprobante::factory()->count(3)->create();

        $this->actingAs($comisario)
            ->get(route('comprobantes.index'))
            ->assertOk()
            ->assertViewIs('comprobantes.index');
    }
}
