<?php
// tests/Feature/FiscalizacionTest.php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Enums\TipoIncidente;
use App\Models\AgenteParqueo;
use App\Models\TurnoAgente;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del módulo de Fiscalización — Turnos, Recorridos e Incidentes (Fase 9.5.4).
 *
 * Cubre (Art. 38 — Ordenanza SIMETSA):
 *  - Agente puede iniciar turno.
 *  - No puede iniciar turno si ya tiene uno activo.
 *  - Agente puede finalizar su turno.
 *  - Registrar posición GPS en turno activo.
 *  - Registrar incidente invoca stub ECU 911.
 *  - Comisario puede ver listado de turnos en backoffice.
 *  - Conductor no puede acceder a endpoints de turnos.
 */
class FiscalizacionTest extends TestCase
{
    use RefreshDatabase;

    private User $agenteUser;
    private User $comisario;
    private User $conductorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
        ]);

        $this->agenteUser   = User::where('email', 'agente@simetsa.gob.ec')->first();
        $this->comisario    = User::where('email', 'comisario@simetsa.gob.ec')->first();
        $this->conductorUser= User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function agente(): AgenteParqueo
    {
        return AgenteParqueo::firstOrCreate(
            ['user_id' => $this->agenteUser->id],
            [
                'codigo'                   => 'AG-TEST1',
                'numero_credencial'        => 'C-TEST1',
                'carta_compromiso_firmada' => true,
                'fecha_autorizacion'       => now()->toDateString(),
                'estado'                   => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private function crearTurnoActivo(): TurnoAgente
    {
        $agente = $this->agente();

        return TurnoAgente::create([
            'agente_parqueo_id' => $agente->id,
            'inicio_at'         => now(),
            'estado'            => EstadoTurno::Iniciado->value,
        ]);
    }

    // ── Iniciar turno ─────────────────────────────────────────────────────────

    /** Agente puede iniciar un turno correctamente. */
    public function test_agente_puede_iniciar_turno(): void
    {
        $this->agente(); // ensure exists

        $response = $this->withToken($this->token($this->agenteUser))
            ->postJson('/api/v1/turnos', ['observaciones' => 'Inicio de jornada'])
            ->assertCreated();

        $this->assertTrue($response->json('exito'));
        $this->assertEquals('iniciado', $response->json('datos.estado'));

        $this->assertDatabaseHas('turnos_agente', [
            'agente_parqueo_id' => $this->agente()->id,
            'estado'            => EstadoTurno::Iniciado->value,
        ]);
    }

    /** No se puede iniciar un turno si ya hay uno activo. */
    public function test_no_puede_iniciar_turno_si_ya_tiene_uno_activo(): void
    {
        $this->crearTurnoActivo();

        $response = $this->withToken($this->token($this->agenteUser))
            ->postJson('/api/v1/turnos', [])
            ->assertUnprocessable();

        $this->assertFalse($response->json('exito'));
        $this->assertStringContainsString('turno activo', $response->json('mensaje'));
    }

    // ── Finalizar turno ───────────────────────────────────────────────────────

    /** Agente puede finalizar su turno activo. */
    public function test_agente_puede_finalizar_turno(): void
    {
        $turno = $this->crearTurnoActivo();

        $response = $this->withToken($this->token($this->agenteUser))
            ->patchJson("/api/v1/turnos/{$turno->id}/finalizar", [
                'observaciones' => 'Fin de jornada sin novedades.',
            ])
            ->assertOk();

        $this->assertTrue($response->json('exito'));
        $this->assertEquals('finalizado', $response->json('datos.estado'));
        $this->assertNotNull($response->json('datos.fin_at'));

        $this->assertDatabaseHas('turnos_agente', [
            'id'    => $turno->id,
            'estado'=> EstadoTurno::Finalizado->value,
        ]);
    }

    // ── Registrar posición GPS ────────────────────────────────────────────────

    /** Agente puede registrar posición GPS durante turno activo. */
    public function test_agente_puede_registrar_posicion_gps(): void
    {
        $this->crearTurnoActivo();

        $response = $this->withToken($this->token($this->agenteUser))
            ->postJson('/api/v1/recorridos', [
                'latitud'  => -1.0474,
                'longitud' => -78.5819,
            ])
            ->assertCreated();

        $this->assertTrue($response->json('exito'));
        $this->assertEquals(-1.0474, $response->json('datos.latitud'));

        $this->assertDatabaseHas('recorridos_agente', [
            'latitud'  => -1.0474,
            'longitud' => -78.5819,
        ]);
    }

    // ── Registrar incidente ───────────────────────────────────────────────────

    /**
     * Agente puede registrar incidente; el stub ECU 911 marca el incidente
     * como reportado (reportado_ecu911 = true) sin hacer HTTP real.
     */
    public function test_agente_puede_registrar_incidente_y_stub_ecu911_se_llama(): void
    {
        $this->crearTurnoActivo();

        $response = $this->withToken($this->token($this->agenteUser))
            ->postJson('/api/v1/incidentes', [
                'tipo'        => TipoIncidente::Accidente->value,
                'descripcion' => 'Choque leve entre dos vehículos en la esquina.',
                'latitud'     => -1.0470,
                'longitud'    => -78.5820,
            ])
            ->assertCreated();

        $this->assertTrue($response->json('exito'));
        $this->assertTrue($response->json('datos.reportado_ecu911'));
        $this->assertNotNull($response->json('datos.notificado_at'));

        // El stub ECU 911 retorna true → el incidente queda marcado en BD
        $this->assertDatabaseHas('incidentes_calle', [
            'tipo'             => TipoIncidente::Accidente->value,
            'reportado_ecu911' => true,
        ]);
    }

    // ── Backoffice ────────────────────────────────────────────────────────────

    /** Comisario puede ver el listado de turnos en el backoffice. */
    public function test_comisario_puede_ver_turnos_en_backoffice(): void
    {
        $this->crearTurnoActivo();

        $this->actingAs($this->comisario)
            ->get(route('turnos.index'))
            ->assertOk()
            ->assertSee('Turnos de Agentes');
    }

    // ── Autorización ──────────────────────────────────────────────────────────

    /** Conductor no tiene permiso para iniciar turnos ni ver recorridos. */
    public function test_conductor_no_puede_acceder_a_endpoints_de_turnos(): void
    {
        $this->withToken($this->token($this->conductorUser))
            ->postJson('/api/v1/turnos', [])
            ->assertForbidden();

        $this->withToken($this->token($this->conductorUser))
            ->postJson('/api/v1/recorridos', ['latitud' => -1.0, 'longitud' => -78.0])
            ->assertForbidden();

        $this->withToken($this->token($this->conductorUser))
            ->postJson('/api/v1/incidentes', [
                'tipo' => TipoIncidente::Otro->value, 'descripcion' => 'test',
            ])
            ->assertForbidden();
    }
}
