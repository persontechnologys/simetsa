<?php
// tests/Feature/VehiculoExoneradoControllerTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehiculoExonerado;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del CRUD de vehículos exonerados (Fase 4.D — Art. 27 Ordenanza SIMETSA).
 */
class VehiculoExoneradoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function agenteUser(): User
    {
        return User::where('email', 'agente@simetsa.gob.ec')->first();
    }

    private function datosValidos(): array
    {
        return [
            'placa'            => 'POL-0001',
            'institucion'      => 'Policía Nacional del Ecuador',
            'tipo_exoneracion' => 'policia',
            'fecha_registro'   => now()->format('Y-m-d'),
            'activo'           => '1',
        ];
    }

    // ===== Listado =====

    public function test_comisario_puede_ver_listado(): void
    {
        $this->actingAs($this->comisarioUser())
            ->get(route('vehiculos-exonerados.index'))
            ->assertOk();
    }

    public function test_conductor_no_puede_ver_listado(): void
    {
        $this->actingAs($this->conductorUser())
            ->get(route('vehiculos-exonerados.index'))
            ->assertForbidden();
    }

    public function test_agente_no_puede_ver_listado(): void
    {
        $this->actingAs($this->agenteUser())
            ->get(route('vehiculos-exonerados.index'))
            ->assertForbidden();
    }

    // ===== Creación =====

    public function test_comisario_puede_registrar_vehiculo_exonerado(): void
    {
        $this->actingAs($this->comisarioUser())
            ->post(route('vehiculos-exonerados.store'), $this->datosValidos())
            ->assertRedirect(route('vehiculos-exonerados.index'));

        $this->assertDatabaseHas('vehiculos_exonerados', [
            'placa'       => 'POL-0001',
            'institucion' => 'Policía Nacional del Ecuador',
        ]);
    }

    public function test_tipo_exoneracion_invalido_falla_validacion(): void
    {
        $this->actingAs($this->comisarioUser())
            ->post(route('vehiculos-exonerados.store'), array_merge($this->datosValidos(), [
                'tipo_exoneracion' => 'tipo_invalido',
            ]))
            ->assertSessionHasErrors('tipo_exoneracion');
    }

    public function test_tiempo_maximo_mayor_a_2_horas_falla(): void
    {
        $this->actingAs($this->comisarioUser())
            ->post(route('vehiculos-exonerados.store'), array_merge($this->datosValidos(), [
                'tiempo_maximo_horas' => 5,
            ]))
            ->assertSessionHasErrors('tiempo_maximo_horas');
    }

    // ===== Edición =====

    public function test_comisario_puede_editar_vehiculo_exonerado(): void
    {
        $vehiculo = VehiculoExonerado::factory()->create([
            'registrado_por' => $this->comisarioUser()->id,
        ]);

        $this->actingAs($this->comisarioUser())
            ->put(route('vehiculos-exonerados.update', $vehiculo), array_merge($this->datosValidos(), [
                'placa'       => 'BOM-0002',
                'institucion' => 'Cuerpo de Bomberos Salcedo',
                'tipo_exoneracion' => 'bomberos',
            ]))
            ->assertRedirect(route('vehiculos-exonerados.index'));

        $this->assertEquals('BOM-0002', $vehiculo->fresh()->placa);
    }

    // ===== Toggle activar/suspender (Art. 27) =====

    public function test_comisario_puede_suspender_exoneracion_activa(): void
    {
        $vehiculo = VehiculoExonerado::factory()->create([
            'registrado_por' => $this->comisarioUser()->id,
            'activo'         => true,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('vehiculos-exonerados.toggle', $vehiculo))
            ->assertRedirect();

        $this->assertFalse($vehiculo->fresh()->activo);
    }

    public function test_comisario_puede_activar_exoneracion_suspendida(): void
    {
        $vehiculo = VehiculoExonerado::factory()->create([
            'registrado_por' => $this->comisarioUser()->id,
            'activo'         => false,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('vehiculos-exonerados.toggle', $vehiculo))
            ->assertRedirect();

        $this->assertTrue($vehiculo->fresh()->activo);
    }

    // ===== Eliminación =====

    public function test_comisario_puede_eliminar_vehiculo_exonerado(): void
    {
        $vehiculo = VehiculoExonerado::factory()->create([
            'registrado_por' => $this->comisarioUser()->id,
        ]);

        $this->actingAs($this->comisarioUser())
            ->delete(route('vehiculos-exonerados.destroy', $vehiculo))
            ->assertRedirect(route('vehiculos-exonerados.index'));

        $this->assertSoftDeleted('vehiculos_exonerados', ['id' => $vehiculo->id]);
    }
}
