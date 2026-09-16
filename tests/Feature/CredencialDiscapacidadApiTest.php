<?php
// tests/Feature/CredencialDiscapacidadApiTest.php

namespace Tests\Feature;

use App\Models\Conductor;
use App\Models\CredencialDiscapacidad;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TipoVehiculoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests de credenciales CONADIS (Fase 4.C — Art. 26 Ordenanza SIMETSA).
 *
 * La credencial es personal del conductor (no del vehículo) desde la migración
 * 2026_06_10 — todos los endpoints usan /conductor/credencial.
 */
class CredencialDiscapacidadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, TipoVehiculoSeeder::class]);
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function crearConductor(?User $user = null): Conductor
    {
        $user ??= $this->conductorUser();

        return Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-' . str_pad((string) ($user->id + 90000), 5, '0', STR_PAD_LEFT), 'estado' => Conductor::ESTADO_ACTIVO],
        );
    }

    private function crearVehiculo(?Conductor $conductor = null): Vehiculo
    {
        $conductor ??= $this->crearConductor();

        return Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => TipoVehiculo::where('codigo', 'liviano_privado')->first()->id,
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private function datosCredencial(): array
    {
        return [
            'numero_conadis'          => '17-AB12-CONADIS',
            'nombre_beneficiario'     => 'Pedro Tigse Caisaguano',
            'fecha_emision'           => '2023-01-15',
            'porcentaje_discapacidad' => 45,
        ];
    }

    // ===== API — Autenticación =====

    public function test_registrar_credencial_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/conductor/credencial', [])->assertUnauthorized();
    }

    // ===== API — Registro =====

    public function test_conductor_puede_registrar_credencial_sin_archivo(): void
    {
        $this->crearConductor();

        $response = $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/conductor/credencial', $this->datosCredencial())
            ->assertCreated();

        $this->assertTrue($response->json('exito'));
        $this->assertEquals('pendiente', $response->json('datos.estado'));
        $this->assertDatabaseHas('credenciales_discapacidad', [
            'numero_conadis' => '17-AB12-CONADIS',
        ]);
    }

    public function test_conductor_puede_registrar_credencial_con_archivo(): void
    {
        Storage::fake('public');
        $this->crearConductor();

        $response = $this->withToken($this->token($this->conductorUser()))
            ->post('/api/v1/conductor/credencial', array_merge(
                $this->datosCredencial(),
                ['archivo' => UploadedFile::fake()->create('credencial.pdf', 200, 'application/pdf')],
            ), ['Accept' => 'application/json']);

        $response->assertCreated();
        $this->assertNotNull($response->json('datos.url_archivo'));
    }

    public function test_segunda_credencial_activa_del_mismo_conductor_falla(): void
    {
        $conductor = $this->crearConductor();

        CredencialDiscapacidad::factory()->create([
            'conductor_id' => $conductor->id,
            'estado'       => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/conductor/credencial', $this->datosCredencial())
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    public function test_puede_registrar_nueva_credencial_si_anterior_fue_rechazada(): void
    {
        $conductor = $this->crearConductor();

        CredencialDiscapacidad::factory()->rechazada()->create(['conductor_id' => $conductor->id]);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/conductor/credencial', $this->datosCredencial())
            ->assertCreated();
    }

    // ===== API — Consulta =====

    public function test_conductor_puede_ver_su_credencial(): void
    {
        $conductor = $this->crearConductor();

        CredencialDiscapacidad::factory()->create([
            'conductor_id'   => $conductor->id,
            'numero_conadis' => '17-TESTCONADIS',
        ]);

        $response = $this->withToken($this->token($this->conductorUser()))
            ->getJson('/api/v1/conductor/credencial')
            ->assertOk();

        $this->assertEquals('17-TESTCONADIS', $response->json('datos.numero_conadis'));
    }

    public function test_conductor_sin_credencial_retorna_404(): void
    {
        $this->crearConductor();

        $this->withToken($this->token($this->conductorUser()))
            ->getJson('/api/v1/conductor/credencial')
            ->assertNotFound();
    }

    // ===== Web backoffice — Aprobar / Rechazar =====

    public function test_comisario_puede_aprobar_credencial_pendiente(): void
    {
        $conductor = $this->crearConductor();
        $credencial = CredencialDiscapacidad::factory()->create([
            'conductor_id' => $conductor->id,
            'estado'       => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('credenciales-discapacidad.aprobar', $credencial))
            ->assertRedirect();

        $this->assertEquals(CredencialDiscapacidad::ESTADO_APROBADA, $credencial->fresh()->estado);
        $this->assertNotNull($credencial->fresh()->aprobada_por);
    }

    public function test_comisario_puede_rechazar_credencial_pendiente(): void
    {
        $conductor = $this->crearConductor();
        $credencial = CredencialDiscapacidad::factory()->create([
            'conductor_id' => $conductor->id,
            'estado'       => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('credenciales-discapacidad.rechazar', $credencial), [
                'observaciones' => 'Número CONADIS no corresponde al beneficiario declarado.',
            ])
            ->assertRedirect();

        $this->assertEquals(CredencialDiscapacidad::ESTADO_RECHAZADA, $credencial->fresh()->estado);
    }

    public function test_rechazar_sin_observaciones_redirige_con_error(): void
    {
        $conductor = $this->crearConductor();
        $credencial = CredencialDiscapacidad::factory()->create([
            'conductor_id' => $conductor->id,
            'estado'       => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('credenciales-discapacidad.rechazar', $credencial), [
                'observaciones' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(CredencialDiscapacidad::ESTADO_PENDIENTE, $credencial->fresh()->estado);
    }

    public function test_conductor_no_puede_aprobar_credencial(): void
    {
        $conductor = $this->crearConductor();
        $credencial = CredencialDiscapacidad::factory()->create([
            'conductor_id' => $conductor->id,
        ]);

        $this->actingAs($this->conductorUser())
            ->patch(route('credenciales-discapacidad.aprobar', $credencial))
            ->assertForbidden();
    }
}
