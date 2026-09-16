<?php

// tests/Feature/InmovilizacionTest.php

namespace Tests\Feature;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoInmovilizacion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use App\Models\User;
use App\Models\Zona;
use App\Services\InfraccionService;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests standalone del módulo de Inmovilizaciones (Fase 9.5.3).
 *
 * Cubre:
 *  - Inmovilizar vehículo (Art. 15 Ordenanza SIMETSA).
 *  - Liberar inmovilización tras pago.
 *  - Restricción de doble inmovilización.
 *  - Liberación administrativa con motivo.
 *  - Autorización del backoffice (GET /inmovilizaciones).
 */
class InmovilizacionTest extends TestCase
{
    use RefreshDatabase;

    private InfraccionService $service;
    private User $comisario;
    private User $conductorUser;

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
        $this->service      = app(InfraccionService::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function agente(): AgenteParqueo
    {
        $user = User::where('email', 'agente@simetsa.gob.ec')->first();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'                  => 'AG-TEST1',
                'numero_credencial'       => 'C-TEST1',
                'carta_compromiso_firmada'=> true,
                'fecha_autorizacion'      => now()->toDateString(),
                'estado'                  => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function zona(): Zona
    {
        return Zona::where('codigo', 'centro')->first();
    }

    private function crearInfraccion(EstadoInfraccion $estado = EstadoInfraccion::Pendiente): Infraccion
    {
        return Infraccion::create([
            'placa'             => 'TST1234',
            'zona_id'           => $this->zona()->id,
            'agente_parqueo_id' => $this->agente()->id,
            'tipo_infraccion'   => TipoInfraccion::SinTicketVisible,
            'estado'            => $estado,
            'monto_multa'       => 9.20,
            'sbu_vigente'       => 460.00,
        ]);
    }

    // ── Inmovilizar ───────────────────────────────────────────────────────────

    /** Agente puede inmovilizar un vehículo infraccionado. */
    public function test_inmovilizar_crea_registro(): void
    {
        $infraccion = $this->crearInfraccion();

        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), [
            'notas' => 'Vehículo sin ticket visible.',
        ]);

        $this->assertInstanceOf(Inmovilizacion::class, $inmovilizacion);
        $this->assertEquals(EstadoInmovilizacion::Activa, $inmovilizacion->estado);
        $this->assertDatabaseHas('inmovilizaciones', [
            'infraccion_id' => $infraccion->id,
            'estado'        => EstadoInmovilizacion::Activa->value,
        ]);
    }

    /**
     * No se puede inmovilizar un vehículo que ya tiene inmovilización activa.
     * El service comprueba la relación: se necesita refrescar el modelo para que
     * la relación cacheada quede obsoleta y se detecte la inmovilización previa.
     */
    public function test_no_se_puede_inmovilizar_dos_veces(): void
    {
        $infraccion = $this->crearInfraccion();
        $this->service->inmovilizar($infraccion, $this->agente(), []);

        // Refrescar para que el service detecte la relación inmovilizacion cargada en BD.
        $this->expectException(DomainException::class);
        $this->service->inmovilizar($infraccion->fresh(), $this->agente(), []);
    }

    /**
     * Inmovilizar NO cambia el estado de la infracción — el estado permanece
     * 'pendiente'. El cambio de estado de la infracción ocurre al pagar.
     */
    public function test_inmovilizar_mantiene_infraccion_pendiente(): void
    {
        $infraccion = $this->crearInfraccion();
        $this->service->inmovilizar($infraccion, $this->agente(), []);

        $this->assertEquals(EstadoInfraccion::Pendiente, $infraccion->fresh()->estado);
    }

    // ── Liberar ───────────────────────────────────────────────────────────────

    /** No se puede liberar sin motivo si la infracción no está pagada. */
    public function test_no_se_puede_liberar_sin_pago_y_sin_motivo(): void
    {
        $infraccion = $this->crearInfraccion();
        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), []);

        $this->expectException(DomainException::class);
        $this->service->liberar($inmovilizacion, null);
    }

    /** Se puede liberar con motivo aunque la infracción no esté pagada (liberación forzada). */
    public function test_se_puede_liberar_con_motivo_sin_pago(): void
    {
        $infraccion = $this->crearInfraccion();
        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), []);

        $liberada = $this->service->liberar($inmovilizacion, 'Error de procedimiento, liberación administrativa.');

        $this->assertEquals(EstadoInmovilizacion::Liberada, $liberada->estado);
        $this->assertNotNull($liberada->liberada_en);
    }

    /** No se puede liberar una inmovilización que ya fue liberada. */
    public function test_no_se_puede_liberar_inmovilizacion_ya_liberada(): void
    {
        $infraccion = $this->crearInfraccion();
        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), []);
        $this->service->liberar($inmovilizacion, 'Primera liberación.');

        $this->expectException(DomainException::class);
        $this->service->liberar($inmovilizacion->fresh(), 'Segunda liberación — no debe pasar.');
    }

    /** Liberar inmovilización pagada no requiere motivo. */
    public function test_liberar_tras_pago_no_requiere_motivo(): void
    {
        $infraccion = $this->crearInfraccion(EstadoInfraccion::Pagada);
        $inmovilizacion = Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $this->agente()->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now()->subMinutes(30),
        ]);

        $liberada = $this->service->liberar($inmovilizacion);

        $this->assertEquals(EstadoInmovilizacion::Liberada, $liberada->estado);
    }

    // ── Backoffice: autorización ──────────────────────────────────────────────

    /** Invitado es redirigido al login. */
    public function test_invitado_no_puede_ver_listado(): void
    {
        $this->get(route('inmovilizaciones.index'))->assertRedirect('/login');
    }

    /** Conductor no puede acceder al backoffice de inmovilizaciones. */
    public function test_conductor_no_puede_ver_listado(): void
    {
        $this->actingAs($this->conductorUser)
             ->get(route('inmovilizaciones.index'))
             ->assertForbidden();
    }

    /** Comisario puede ver el listado de inmovilizaciones. */
    public function test_comisario_puede_ver_listado(): void
    {
        $this->actingAs($this->comisario)
             ->get(route('inmovilizaciones.index'))
             ->assertOk()
             ->assertViewIs('inmovilizaciones.index')
             ->assertViewHas('inmovilizaciones');
    }

    /** Comisario puede ver el detalle de una inmovilización. */
    public function test_comisario_puede_ver_detalle(): void
    {
        $infraccion = $this->crearInfraccion();
        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), []);

        $this->actingAs($this->comisario)
             ->get(route('inmovilizaciones.show', $inmovilizacion))
             ->assertOk()
             ->assertViewIs('inmovilizaciones.show')
             ->assertViewHas('inmovilizacion');
    }

    // ── Backoffice: acción liberar ────────────────────────────────────────────

    /** Comisario puede liberar un vehículo inmovilizado desde el backoffice. */
    public function test_comisario_puede_liberar_desde_backoffice(): void
    {
        $infraccion = $this->crearInfraccion();
        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), []);

        $this->actingAs($this->comisario)
             ->patch(route('inmovilizaciones.liberar', $inmovilizacion), [
                 'motivo' => 'Error en el procedimiento de inmovilización.',
             ])
             ->assertRedirect(route('inmovilizaciones.show', $inmovilizacion));

        $this->assertEquals(EstadoInmovilizacion::Liberada, $inmovilizacion->fresh()->estado);
    }

    /** La liberación sin motivo desde el backoffice falla por validación. */
    public function test_liberar_sin_motivo_falla_validacion(): void
    {
        $infraccion = $this->crearInfraccion();
        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), []);

        $this->actingAs($this->comisario)
             ->patch(route('inmovilizaciones.liberar', $inmovilizacion), [
                 'motivo' => '',
             ])
             ->assertSessionHasErrors('motivo');
    }

    /** Conductor no puede ejecutar la acción liberar del backoffice. */
    public function test_conductor_no_puede_liberar_desde_backoffice(): void
    {
        $infraccion = $this->crearInfraccion();
        $inmovilizacion = $this->service->inmovilizar($infraccion, $this->agente(), []);

        $this->actingAs($this->conductorUser)
             ->patch(route('inmovilizaciones.liberar', $inmovilizacion), [
                 'motivo' => 'Intento no autorizado.',
             ])
             ->assertForbidden();
    }
}
