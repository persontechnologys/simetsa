<?php

// tests/Feature/TicketServiceTest.php

namespace Tests\Feature;

use App\Enums\EstadoSesionParqueo;
use App\Enums\EstadoTicket;
use App\Enums\TipoCancelacion;
use App\Models\Cancelacion;
use App\Models\Conductor;
use App\Models\CredencialDiscapacidad;
use App\Models\DiaFeriado;
use App\Models\SesionParqueo;
use App\Models\Ticket;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoExonerado;
use App\Models\Zona;
use App\Services\TicketService;
use Carbon\Carbon;
use Database\Seeders\HorarioOperacionSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de las reglas de negocio del TicketService (Fase 5.B — Arts. 12–14, 22, 26, 27 Ordenanza SIMETSA).
 */
class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketService $service;

    /** Martes 2026-03-03 10:00 (dentro de horario operativo) */
    private const HORA_OPERATIVA = '2026-03-03 10:00:00';

    /** Martes 2026-03-03 20:00 (fuera de horario operativo) */
    private const HORA_FUERA = '2026-03-03 20:00:00';

    /** Lunes 2026-03-02 10:00 (día no operativo) */
    private const DIA_NO_OPERATIVO = '2026-03-02 10:00:00';

    /** Martes 2026-03-03 17:30 (dentro de horario pero cerca del cierre) */
    private const HORA_CASI_CIERRE = '2026-03-03 17:30:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            HorarioOperacionSeeder::class,
            ZonaSeeder::class,
        ]);
        $this->service = app(TicketService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Limpia el mock de tiempo después de cada test
        parent::tearDown();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function crearConductorConVehiculo(?string $placa = null): array
    {
        $user      = $this->conductorUser();
        $conductor = Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-TEST1', 'estado' => Conductor::ESTADO_ACTIVO]
        );

        $tipo     = TipoVehiculo::factory()->create(['aplica_tarifa' => true]);
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => $placa ?? 'ABC-1234',
            'estado'           => Vehiculo::ESTADO_ACTIVO,
        ]);

        return [$conductor, $vehiculo];
    }

    private function zona(): Zona
    {
        return Zona::where('codigo', 'centro')->first();
    }

    private function datosCompra(int $conductorId, int $vehiculoId, int $horas = 1): array
    {
        return [
            'conductor_id'    => $conductorId,
            'vehiculo_id'     => $vehiculoId,
            'zona_id'         => $this->zona()->id,
            'calle_id'        => null,
            'horas_compradas' => $horas,
            'metodo_pago'     => 'efectivo',
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 1: Compra en horario operativo normal → ticket creado
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_en_horario_operativo_crea_ticket_correctamente(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $ticket = $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 1));

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals(EstadoTicket::Pendiente, $ticket->estado);
        $this->assertEquals(1, $ticket->horas_compradas);
        $this->assertEquals(0.25, (float) $ticket->monto);
        $this->assertFalse($ticket->es_exonerado);
        $this->assertNotEmpty($ticket->codigo);
        $this->assertStringStartsWith('T-2026-', $ticket->codigo);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 2: Compra fuera de horario → DomainException (Art. 12)
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_fuera_de_horario_operativo_lanza_excepcion(): void
    {
        Carbon::setTestNow(self::HORA_FUERA); // Martes 20:00
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Fuera del horario operativo/i');

        $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id));
    }

    public function test_compra_en_dia_no_operativo_lanza_excepcion(): void
    {
        Carbon::setTestNow(self::DIA_NO_OPERATIVO); // Lunes
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/no opera el día de hoy/i');

        $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id));
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 3: Compra en día feriado → DomainException (Art. 12)
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_en_dia_feriado_lanza_excepcion(): void
    {
        $fecha = '2026-03-03'; // Martes (normalmente operativo)
        Carbon::setTestNow("{$fecha} 10:00:00");

        // Crear feriado para ese día
        DiaFeriado::create([
            'fecha'      => $fecha,
            'nombre'     => 'Feriado de prueba',
            'tipo'       => DiaFeriado::TIPO_CANTONAL,
            'recurrente' => false,
            'activo'     => true,
        ]);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/día feriado/i');

        $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id));
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 4: Vehículo con CredencialDiscapacidad activa → monto = 0 (Art. 26)
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_con_credencial_discapacidad_aprobada_es_exonerada(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        // Crear credencial CONADIS aprobada para el conductor (Art. 26 — la credencial es del conductor, no del vehículo)
        CredencialDiscapacidad::factory()->aprobada()->create([
            'conductor_id'     => $conductor->id,
            'fecha_vencimiento' => null, // Sin vencimiento
        ]);

        $ticket = $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 2));

        $this->assertEquals(0.00, (float) $ticket->monto);
        $this->assertTrue($ticket->es_exonerado);
        $this->assertEquals('conadis', $ticket->tipo_exoneracion);
    }

    public function test_credencial_discapacidad_rechazada_no_exonera(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        // Credencial rechazada → no exonera
        CredencialDiscapacidad::factory()->rechazada()->create([
            'conductor_id' => $conductor->id,
        ]);

        $ticket = $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 1));

        $this->assertFalse($ticket->es_exonerado);
        $this->assertEquals(0.25, (float) $ticket->monto);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 5: Vehículo en VehiculoExonerado activo → monto = 0 (Art. 27)
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_con_vehiculo_exonerado_institucional_es_gratis(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $admin    = User::where('email', 'admin@simetsa.gob.ec')->first();
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo('POL-1234');

        // Registrar la placa como vehículo exonerado institucional
        VehiculoExonerado::factory()->create([
            'placa'            => 'POL-1234',
            'tipo_exoneracion' => VehiculoExonerado::TIPO_POLICIA,
            'activo'           => true,
            'registrado_por'   => $admin->id,
        ]);

        $ticket = $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 1));

        $this->assertEquals(0.00, (float) $ticket->monto);
        $this->assertTrue($ticket->es_exonerado);
        $this->assertEquals('institucional', $ticket->tipo_exoneracion);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 6: Compra de 3 horas → DomainException (Art. 14)
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_mas_de_dos_horas_lanza_excepcion(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/máximo de parqueo es 2 horas/i');

        $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 3));
    }

    public function test_compra_de_cero_horas_lanza_excepcion(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $this->expectException(DomainException::class);

        $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 0));
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 7: Compra que cruza cierre de jornada → DomainException (D4)
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_que_cruza_cierre_de_jornada_lanza_excepcion(): void
    {
        // 17:30 + 2h = 19:30 → cruza el cierre de 18:00
        Carbon::setTestNow(self::HORA_CASI_CIERRE);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/cierre de operaciones/i');

        $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 2));
    }

    public function test_compra_de_1_hora_en_horario_casi_cierre_funciona(): void
    {
        // 17:30 + 1h = 18:30 → en realidad sigue cruzando... espera:
        // 17:30 + 1h = 18:30 > 18:00 → también cruza!
        // Necesito usar 16:30 para que 1h sea válido
        Carbon::setTestNow('2026-03-03 16:30:00'); // 16:30 + 1h = 17:30 < 18:00
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $ticket = $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 1));

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals(EstadoTicket::Pendiente, $ticket->estado);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 8: Validar placa con ticket activo → estado 'activo'
    // ────────────────────────────────────────────────────────────────────────

    public function test_validar_placa_con_ticket_activo_devuelve_estado_activo(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo('XYZ-9001');

        // Crear ticket con estado activo + sesión de parqueo asociada
        $ticket = Ticket::create([
            'codigo'          => 'T-2026-00001',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Activo,
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subMinutes(10),
            'expira_en'       => now()->addMinutes(50),
        ]);

        // La sesión confirma que el agente inició el estacionamiento
        SesionParqueo::create([
            'ticket_id'        => $ticket->id,
            'inicio_at'        => now()->subMinutes(10),
            'fin_programado_at'=> now()->addMinutes(50),
            'estado'           => EstadoSesionParqueo::Activa,
        ]);

        $resultado = $this->service->validarPorPlaca('XYZ-9001', now());

        $this->assertEquals('activo', $resultado['estado']);
        $this->assertNotNull($resultado['ticket']);
        $this->assertGreaterThan(0, $resultado['minutos_restantes']);
        $this->assertFalse($resultado['en_tolerancia']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 9: Ticket vencido hace 3 min → estado 'en_tolerancia' (Art. 13)
    // ────────────────────────────────────────────────────────────────────────

    public function test_validar_placa_ticket_vencido_3_min_es_en_tolerancia(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo('TOL-0003');

        $expiraEn = now()->subMinutes(3); // venció hace 3 minutos

        Ticket::create([
            'codigo'          => 'T-2026-00002',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Activo, // Estado previo en BD
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subMinutes(63),
            'expira_en'       => $expiraEn,
        ]);

        $resultado = $this->service->validarPorPlaca('TOL-0003', now());

        $this->assertEquals('en_tolerancia', $resultado['estado']);
        $this->assertTrue($resultado['en_tolerancia']);
        $this->assertNotNull($resultado['tolerancia_expira_en']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 10: Ticket vencido hace 7 min → estado 'expirado' (fuera de tolerancia)
    // ────────────────────────────────────────────────────────────────────────

    public function test_validar_placa_ticket_vencido_7_min_es_expirado(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo('EXP-0007');

        $expiraEn = now()->subMinutes(7); // venció hace 7 min (> 5 min de tolerancia)

        Ticket::create([
            'codigo'          => 'T-2026-00003',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Activo,
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subMinutes(67),
            'expira_en'       => $expiraEn,
        ]);

        $resultado = $this->service->validarPorPlaca('EXP-0007', now());

        $this->assertEquals('expirado', $resultado['estado']);
        $this->assertFalse($resultado['en_tolerancia']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 11: Conductor cancela ticket propio (antes de sesión)
    // ────────────────────────────────────────────────────────────────────────

    public function test_conductor_puede_cancelar_ticket_pendiente(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $user = $this->conductorUser();

        $ticket = Ticket::create([
            'codigo'          => 'T-2026-00010',
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

        $cancelacion = $this->service->cancelar($ticket, $user, 'Ya no necesito el espacio.');

        $this->assertInstanceOf(Cancelacion::class, $cancelacion);
        $this->assertEquals(TipoCancelacion::Conductor, $cancelacion->tipo);
        $this->assertEquals(EstadoTicket::Cancelado, $ticket->fresh()->estado);
        $this->assertDatabaseHas('cancelaciones', [
            'ticket_id'     => $ticket->id,
            'cancelado_por' => $user->id,
        ]);
    }

    public function test_conductor_no_puede_cancelar_ticket_activo(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $user = $this->conductorUser();

        $ticket = Ticket::create([
            'codigo'          => 'T-2026-00011',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Activo, // Ya tiene sesión iniciada
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subMinutes(5),
            'expira_en'       => now()->addMinutes(55),
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/No se puede cancelar/i');

        $this->service->cancelar($ticket, $user, 'Intento inválido.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Caso 12: Comisario anula ticket activo → Cancelacion tipo admin
    // ────────────────────────────────────────────────────────────────────────

    public function test_comisario_puede_anular_ticket_activo(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $comisario = $this->comisarioUser();

        $ticket = Ticket::create([
            'codigo'          => 'T-2026-00020',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Activo,
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subMinutes(10),
            'expira_en'       => now()->addMinutes(50),
        ]);

        $cancelacion = $this->service->anular($ticket, $comisario, 'Vehículo no retiró a tiempo.');

        $this->assertInstanceOf(Cancelacion::class, $cancelacion);
        $this->assertEquals(TipoCancelacion::Admin, $cancelacion->tipo);
        $this->assertEquals(EstadoTicket::Anulado, $ticket->fresh()->estado);
    }

    public function test_no_se_puede_anular_ticket_ya_cancelado(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $comisario = $this->comisarioUser();

        $ticket = Ticket::create([
            'codigo'          => 'T-2026-00021',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $this->zona()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Cancelado, // Ya cancelado
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subMinutes(5),
            'expira_en'       => now()->addMinutes(55),
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/No se puede anular/i');

        $this->service->anular($ticket, $comisario, 'Intento de anular ticket ya cancelado.');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Adicionales: Ownership y vehicle sin ticket
    // ────────────────────────────────────────────────────────────────────────

    public function test_validar_placa_sin_ticket_devuelve_sin_ticket(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);

        $resultado = $this->service->validarPorPlaca('SIN-0000', now());

        $this->assertEquals('sin_ticket', $resultado['estado']);
        $this->assertNull($resultado['ticket']);
        $this->assertFalse($resultado['en_tolerancia']);
    }

    public function test_no_se_puede_comprar_ticket_si_ya_existe_uno_vigente(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        // Primer ticket (vigente)
        Ticket::create([
            'codigo'          => 'T-2026-00030',
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

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/ya tiene un ticket vigente/i');

        // Segundo ticket para el mismo vehículo
        $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 1));
    }

    public function test_calcular_monto_dos_horas_sin_exoneracion(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $ticket = $this->service->comprar($this->datosCompra($conductor->id, $vehiculo->id, 2));

        // Art. 22: $0.25/hora × 2h = $0.50
        $this->assertEquals(0.50, (float) $ticket->monto);
    }
}
