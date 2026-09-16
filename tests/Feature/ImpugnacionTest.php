<?php

// tests/Feature/ImpugnacionTest.php

namespace Tests\Feature;

use App\Enums\EstadoInfraccion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Conductor;
use App\Models\Impugnacion;
use App\Models\Infraccion;
use App\Models\NotificacionInfraccion;
use App\Models\User;
use App\Models\Zona;
use App\Services\FCMService;
use App\Services\ImpugnacionService;
use App\Services\InfraccionService;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del módulo de Impugnaciones y Notificaciones de Infracción (Fase 9.5.6).
 *
 * Cubre:
 *  - Conductor puede impugnar su infracción (Art. 17.f).
 *  - Ownership: conductor no puede impugnar infracción ajena.
 *  - No se puede impugnar dos veces la misma infracción.
 *  - No se puede impugnar una infracción pagada.
 *  - Comisario puede ver el listado de impugnaciones en backoffice.
 *  - Comisario puede resolver una impugnación (admitir, rechazar, resolver).
 *  - Registrar infracción con conductor_id crea NotificacionInfraccion automáticamente.
 */
class ImpugnacionTest extends TestCase
{
    use RefreshDatabase;

    private User $comisario;
    private User $conductorUser;
    private Conductor $conductor;
    private AgenteParqueo $agente;
    private Zona $zona;
    private ImpugnacionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
            ParametroSeeder::class,
        ]);

        $this->comisario     = User::where('email', 'comisario@simetsa.gob.ec')->first();
        $this->conductorUser = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->zona          = Zona::where('codigo', 'centro')->first();
        $this->conductor     = $this->crearConductor();
        $this->agente        = $this->crearAgente();
        $this->service       = app(ImpugnacionService::class);

        // FCMService en modo silencioso para evitar llamadas reales a Firebase en tests
        $this->mock(FCMService::class, function ($mock) {
            $mock->shouldReceive('enviar')->andThrow(new \RuntimeException('FCM deshabilitado en tests'));
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function crearConductor(): Conductor
    {
        return Conductor::firstOrCreate(
            ['user_id' => $this->conductorUser->id],
            ['codigo' => 'CD-TEST1', 'estado' => Conductor::ESTADO_ACTIVO]
        );
    }

    private function crearAgente(): AgenteParqueo
    {
        $agenteUser = User::where('email', 'agente@simetsa.gob.ec')->first();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $agenteUser->id],
            [
                'codigo'                   => 'AG-TEST1',
                'numero_credencial'        => 'C-TEST1',
                'carta_compromiso_firmada' => true,
                'fecha_autorizacion'       => now()->toDateString(),
                'estado'                   => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function crearInfraccion(array $extra = []): Infraccion
    {
        return Infraccion::create(array_merge([
            'placa'             => 'TST1234',
            'conductor_id'      => $this->conductor->id,
            'zona_id'           => $this->zona->id,
            'agente_parqueo_id' => $this->agente->id,
            'tipo_infraccion'   => TipoInfraccion::SinTicketVisible,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => 9.20,
            'sbu_vigente'       => 460.00,
        ], $extra));
    }

    // ── Impugnación ───────────────────────────────────────────────────────────

    /** Conductor puede presentar impugnación de una infracción propia pendiente (Art. 17.f). */
    public function test_conductor_puede_impugnar_su_infraccion_pendiente(): void
    {
        $infraccion = $this->crearInfraccion();

        $impugnacion = $this->service->presentar(
            $infraccion,
            $this->conductor,
            'Estaba dentro del tiempo permitido por la Ordenanza y cuento con el ticket de pago.'
        );

        $this->assertInstanceOf(Impugnacion::class, $impugnacion);
        $this->assertEquals(Impugnacion::ESTADO_PENDIENTE, $impugnacion->estado);
        $this->assertDatabaseHas('impugnaciones', [
            'infraccion_id' => $infraccion->id,
            'conductor_id'  => $this->conductor->id,
            'estado'        => 'pendiente',
        ]);
    }

    /** Conductor no puede impugnar dos veces la misma infracción (422 via API). */
    public function test_no_se_puede_impugnar_dos_veces(): void
    {
        $infraccion = $this->crearInfraccion();

        $this->service->presentar(
            $infraccion,
            $this->conductor,
            'Primera impugnación — motivo de prueba de veinte caracteres.'
        );

        $this->expectException(DomainException::class);
        $this->service->presentar(
            $infraccion,
            $this->conductor,
            'Segunda impugnación duplicada — no debe ser permitida.'
        );
    }

    /** No se puede impugnar una infracción pagada (estado != pendiente). */
    public function test_no_se_puede_impugnar_infraccion_pagada(): void
    {
        $infraccion = $this->crearInfraccion(['estado' => EstadoInfraccion::Pagada]);

        $this->expectException(DomainException::class);
        $this->service->presentar(
            $infraccion,
            $this->conductor,
            'Intento de impugnar infracción ya pagada — debe ser rechazado.'
        );
    }

    /** Conductor no puede impugnar infracción de otro conductor (ownership via API). */
    public function test_conductor_no_puede_impugnar_infraccion_ajena_via_api(): void
    {
        // Infracción sin conductor_id (placa desconocida)
        $infraccion = $this->crearInfraccion([
            'conductor_id' => null,
            'placa'        => 'AJENA01',
        ]);

        $respuesta = $this->actingAs($this->conductorUser, 'sanctum')
            ->postJson(route('api.impugnaciones.store', $infraccion), [
                'motivo' => 'Motivo de prueba de más de veinte caracteres para cumplir la validación.',
            ]);

        $respuesta->assertForbidden();
    }

    // ── Backoffice — Comisario ────────────────────────────────────────────────

    /** Comisario puede ver el listado de impugnaciones en el backoffice. */
    public function test_comisario_puede_ver_listado_impugnaciones(): void
    {
        $infraccion = $this->crearInfraccion();
        Impugnacion::create([
            'infraccion_id' => $infraccion->id,
            'conductor_id'  => $this->conductor->id,
            'motivo'        => 'Motivo de prueba para test de listado backoffice.',
            'estado'        => 'pendiente',
        ]);

        $this->actingAs($this->comisario)
            ->get(route('impugnaciones.index'))
            ->assertOk()
            ->assertViewIs('impugnaciones.index')
            ->assertViewHas('impugnaciones');
    }

    /** Comisario puede admitir una impugnación pendiente. */
    public function test_comisario_puede_admitir_impugnacion(): void
    {
        $infraccion = $this->crearInfraccion();
        $impugnacion = Impugnacion::create([
            'infraccion_id' => $infraccion->id,
            'conductor_id'  => $this->conductor->id,
            'motivo'        => 'Motivo de prueba de más de veinte caracteres.',
            'estado'        => 'pendiente',
        ]);

        $resultado = $this->service->admitir($impugnacion, $this->comisario);

        $this->assertEquals(Impugnacion::ESTADO_ADMITIDA, $resultado->estado);
        $this->assertEquals($this->comisario->id, $resultado->resuelto_por);
        $this->assertNotNull($resultado->resuelto_at);
    }

    /** Comisario puede resolver una impugnación a favor del conductor. */
    public function test_comisario_puede_resolver_impugnacion(): void
    {
        $infraccion = $this->crearInfraccion();
        $impugnacion = Impugnacion::create([
            'infraccion_id' => $infraccion->id,
            'conductor_id'  => $this->conductor->id,
            'motivo'        => 'El vehículo tenía ticket vigente al momento de la infracción.',
            'estado'        => 'admitida',
        ]);

        $resultado = $this->service->resolver(
            $impugnacion,
            $this->comisario,
            'Se verificó el ticket vigente. La infracción queda anulada administrativamente.'
        );

        $this->assertEquals(Impugnacion::ESTADO_RESUELTA, $resultado->estado);
        $this->assertNotNull($resultado->resolucion);
        $this->assertDatabaseHas('impugnaciones', [
            'id'     => $impugnacion->id,
            'estado' => 'resuelta',
        ]);
    }

    // ── NotificacionInfraccion ────────────────────────────────────────────────

    /**
     * Registrar una infracción con conductor_id crea NotificacionInfraccion automáticamente.
     * El push FCM falla (mock) pero el registro de la infracción y la boleta se crean igual.
     */
    public function test_registrar_infraccion_con_conductor_crea_notificacion(): void
    {
        $infraccionService = app(InfraccionService::class);

        $infraccion = $infraccionService->registrar([
            'placa'           => 'TST1234',
            'tipo_infraccion' => TipoInfraccion::SinTicketVisible,
            'zona_id'         => $this->zona->id,
            'conductor_id'    => $this->conductor->id,
        ], $this->agente);

        $this->assertDatabaseHas('infracciones', ['id' => $infraccion->id]);
        $this->assertDatabaseHas('notificaciones_infraccion', [
            'infraccion_id' => $infraccion->id,
            'conductor_id'  => $this->conductor->id,
        ]);
    }
}
