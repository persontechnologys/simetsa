<?php

// tests/Feature/Api/TicketApiTest.php

namespace Tests\Feature\Api;

use App\Enums\EstadoSesionParqueo;
use App\Enums\EstadoTicket;
use App\Models\Conductor;
use App\Models\SesionParqueo;
use App\Models\Ticket;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Zona;
use Carbon\Carbon;
use Database\Seeders\HorarioOperacionSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de endpoints de tickets para la app móvil del conductor (Fase 5.C).
 * Arts. 13, 14, 19, 22 Ordenanza SIMETSA.
 */
class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    /** Martes 2026-03-03 10:00 (dentro de horario operativo) */
    private const HORA_OPERATIVA = '2026-03-03 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            HorarioOperacionSeeder::class,
            ZonaSeeder::class,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function otroUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('conductor');
        \App\Models\PerfilUsuario::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private function crearConductorConVehiculo(?User $user = null): array
    {
        $user ??= $this->conductorUser();
        $conductor = Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-' . $user->id, 'estado' => Conductor::ESTADO_ACTIVO]
        );
        $tipo     = TipoVehiculo::factory()->create();
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'TST-' . str_pad((string) $conductor->id, 4, '0', STR_PAD_LEFT),
        ]);

        return [$conductor, $vehiculo];
    }

    private function zona(): Zona
    {
        return Zona::where('codigo', 'centro')->first();
    }

    private function ticketPendiente(Conductor $conductor, Vehiculo $vehiculo): Ticket
    {
        return Ticket::create([
            'codigo'          => 'T-2026-' . str_pad((string) rand(1, 9999), 5, '0', STR_PAD_LEFT),
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Pendiente,
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now(),
            'expira_en'       => now()->addHour(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Autenticación
    // ────────────────────────────────────────────────────────────────────────

    public function test_listar_tickets_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
    }

    public function test_comprar_ticket_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/tickets', [])->assertUnauthorized();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Listado de tickets vigentes
    // ────────────────────────────────────────────────────────────────────────

    public function test_conductor_puede_listar_sus_tickets_vigentes(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user = $this->conductorUser();
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo($user);

        $this->ticketPendiente($conductor, $vehiculo);

        $this->withToken($this->token($user))
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonCount(1, 'datos');
    }

    public function test_conductor_no_ve_tickets_de_otros_conductores(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user1 = $this->conductorUser();
        [$conductor1, $vehiculo1] = $this->crearConductorConVehiculo($user1);
        $this->ticketPendiente($conductor1, $vehiculo1);

        // Segundo conductor con ticket propio
        $user2 = $this->otroUser();
        [$conductor2, $vehiculo2] = $this->crearConductorConVehiculo($user2);
        $this->ticketPendiente($conductor2, $vehiculo2);

        // user1 solo debe ver 1 ticket (el suyo)
        $this->withToken($this->token($user1))
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'datos');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Compra de ticket
    // ────────────────────────────────────────────────────────────────────────

    public function test_conductor_puede_comprar_ticket_exitosamente(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user = $this->conductorUser();
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo($user);

        $this->withToken($this->token($user))
            ->postJson('/api/v1/tickets', [
                'vehiculo_id'     => $vehiculo->id,
                'zona_id'         => $this->zona()->id,
                'horas_compradas' => 1,
                'metodo_pago'     => 'efectivo',
            ])
            ->assertCreated()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.ticket.horas_compradas', 1)
            ->assertJsonPath('datos.ticket.monto', 0.25)
            ->assertJsonPath('datos.ticket.estado', 'pendiente')
            ->assertJsonPath('datos.confirmado', true);

        $this->assertDatabaseHas('tickets', ['conductor_id' => $conductor->id]);
    }

    public function test_compra_fuera_de_horario_devuelve_422(): void
    {
        Carbon::setTestNow('2026-03-03 20:00:00'); // Fuera de horario
        $user = $this->conductorUser();
        [, $vehiculo] = $this->crearConductorConVehiculo($user);

        $this->withToken($this->token($user))
            ->postJson('/api/v1/tickets', [
                'vehiculo_id'     => $vehiculo->id,
                'zona_id'         => $this->zona()->id,
                'horas_compradas' => 1,
                'metodo_pago'     => 'efectivo',
            ])
            ->assertStatus(422)
            ->assertJsonPath('exito', false);
    }

    public function test_compra_con_vehiculo_de_otro_conductor_devuelve_422(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user1 = $this->conductorUser();
        $user2 = $this->otroUser();
        // Crear conductor para user1 (necesario para pasar el chequeo de conductor)
        $this->crearConductorConVehiculo($user1);
        [, $vehiculo2] = $this->crearConductorConVehiculo($user2);

        // user1 intenta comprar con vehículo de user2
        $this->withToken($this->token($user1))
            ->postJson('/api/v1/tickets', [
                'vehiculo_id'     => $vehiculo2->id,
                'zona_id'         => $this->zona()->id,
                'horas_compradas' => 1,
                'metodo_pago'     => 'efectivo',
            ])
            ->assertStatus(422)
            ->assertJsonPath('exito', false);
    }

    public function test_compra_sin_conductor_registrado_devuelve_403(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        // Usuario con rol conductor pero SIN registro en tabla conductores
        $user = User::factory()->create();
        $user->assignRole('conductor');

        // Crear un vehículo válido (de otro conductor) para que pase la validación de formato
        $otroConductor = Conductor::factory()->create();
        $tipo          = TipoVehiculo::factory()->create();
        $vehiculo      = Vehiculo::factory()->create([
            'conductor_id'     => $otroConductor->id,
            'tipo_vehiculo_id' => $tipo->id,
        ]);

        $this->withToken($this->token($user))
            ->postJson('/api/v1/tickets', [
                'vehiculo_id'     => $vehiculo->id,
                'zona_id'         => $this->zona()->id,
                'horas_compradas' => 1,
                'metodo_pago'     => 'efectivo',
            ])
            ->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Detalle de ticket
    // ────────────────────────────────────────────────────────────────────────

    public function test_conductor_puede_ver_detalle_de_su_propio_ticket(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user = $this->conductorUser();
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo($user);
        $ticket = $this->ticketPendiente($conductor, $vehiculo);

        $this->withToken($this->token($user))
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('datos.codigo', $ticket->codigo);
    }

    public function test_conductor_no_puede_ver_ticket_de_otro_conductor(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user1 = $this->conductorUser();
        $user2 = $this->otroUser();
        [$conductor2, $vehiculo2] = $this->crearConductorConVehiculo($user2);
        $ticket = $this->ticketPendiente($conductor2, $vehiculo2);

        $this->withToken($this->token($user1))
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Historial
    // ────────────────────────────────────────────────────────────────────────

    public function test_conductor_puede_consultar_su_historial(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user = $this->conductorUser();
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo($user);

        // 3 tickets históricos (cancelados)
        for ($i = 0; $i < 3; $i++) {
            Ticket::create([
                'codigo'          => 'T-2026-H00' . $i,
                'conductor_id'    => $conductor->id,
                'vehiculo_id'     => $vehiculo->id,
                'zona_id'         => $this->zona()->id,
                'horas_compradas' => 1,
                'monto'           => 0.25,
                'estado'          => EstadoTicket::Cancelado,
                'metodo_pago'     => 'efectivo',
                'es_exonerado'    => false,
                'comprado_en'     => now()->subDays($i + 1),
                'expira_en'       => now()->subDays($i + 1)->addHour(),
            ]);
        }

        $response = $this->withToken($this->token($user))
            ->getJson('/api/v1/tickets/historial')
            ->assertOk()
            ->assertJsonPath('exito', true);

        // La respuesta paginada embebe la colección en datos.data (ResourceCollection paginated)
        $this->assertCount(3, $response->json('datos.data') ?? $response->json('datos'));
    }

    // ────────────────────────────────────────────────────────────────────────
    // Cancelar ticket
    // ────────────────────────────────────────────────────────────────────────

    public function test_conductor_puede_cancelar_su_ticket_pendiente(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user = $this->conductorUser();
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo($user);
        $ticket = $this->ticketPendiente($conductor, $vehiculo);

        $this->withToken($this->token($user))
            ->postJson("/api/v1/tickets/{$ticket->id}/cancelar", [
                'motivo' => 'Ya no necesito el espacio de parqueo.',
            ])
            ->assertOk()
            ->assertJsonPath('datos.estado', 'cancelado');

        $this->assertDatabaseHas('cancelaciones', ['ticket_id' => $ticket->id]);
    }

    public function test_conductor_no_puede_cancelar_ticket_de_otro_conductor(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user1 = $this->conductorUser();
        $user2 = $this->otroUser();
        [$conductor2, $vehiculo2] = $this->crearConductorConVehiculo($user2);
        $ticket = $this->ticketPendiente($conductor2, $vehiculo2);

        $this->withToken($this->token($user1))
            ->postJson("/api/v1/tickets/{$ticket->id}/cancelar", [
                'motivo' => 'Intento de cancelar ticket ajeno.',
            ])
            ->assertForbidden();
    }

    public function test_conductor_no_puede_cancelar_ticket_con_sesion_activa(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user = $this->conductorUser();
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo($user);

        $ticket = Ticket::create([
            'codigo'          => 'T-2026-ACTV',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Activo, // Ya tiene sesión
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subMinutes(5),
            'expira_en'       => now()->addMinutes(55),
        ]);

        SesionParqueo::create([
            'ticket_id'        => $ticket->id,
            'inicio_at'        => now()->subMinutes(5),
            'fin_programado_at'=> now()->addMinutes(55),
            'estado'           => EstadoSesionParqueo::Activa,
        ]);

        $this->withToken($this->token($user))
            ->postJson("/api/v1/tickets/{$ticket->id}/cancelar", [
                'motivo' => 'Intento de cancelar ticket activo.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('exito', false);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Validación de formato del request
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_con_campos_vacios_falla_validacion(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $user = $this->conductorUser();
        $this->crearConductorConVehiculo($user);

        // La app usa envelope propio: errores (no errors) con ValidationException (bootstrap/app.php)
        $response = $this->withToken($this->token($user))
            ->postJson('/api/v1/tickets', [])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);

        $errores = $response->json('errores');
        $this->assertArrayHasKey('vehiculo_id', $errores);
        $this->assertArrayHasKey('zona_id', $errores);
        $this->assertArrayHasKey('horas_compradas', $errores);
        $this->assertArrayHasKey('metodo_pago', $errores);
    }
}
