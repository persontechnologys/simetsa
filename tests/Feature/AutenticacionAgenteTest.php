<?php
// tests/Feature/AutenticacionAgenteTest.php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\AgenteParqueo;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Cubre el ciclo de autenticación del agente de parqueo por la API v1 (Fase 9.B):
 * login con credenciales válidas, rechazo por rol/estado incorrecto, perfil y logout.
 */
class AutenticacionAgenteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolPermisoSeeder::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function crearAgenteActivo(array $overrides = []): array
    {
        $user = User::factory()->create([
            'email'    => $overrides['email'] ?? 'agente.test@simetsa.gob.ec',
            'password' => Hash::make($overrides['password'] ?? 'password'),
        ]);
        $user->assignRole(RolSistema::AgenteParqueo->value);

        $agente = AgenteParqueo::create([
            'user_id'                  => $user->id,
            'codigo'                   => 'AG-T001',
            'numero_credencial'        => 'CRED-T001',
            'carta_compromiso_firmada' => true,
            'fecha_autorizacion'       => now()->toDateString(),
            'estado'                   => $overrides['estado'] ?? AgenteParqueo::ESTADO_ACTIVO,
        ]);

        return ['user' => $user, 'agente' => $agente, 'password' => $overrides['password'] ?? 'password'];
    }

    private function tokenDeNuevoAgente(): string
    {
        $datos = $this->crearAgenteActivo();
        return $this->postJson('/api/v1/agente/auth/login', [
            'email'    => $datos['user']->email,
            'password' => $datos['password'],
        ])->json('datos.token');
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_login_agente_devuelve_token_y_datos(): void
    {
        $datos = $this->crearAgenteActivo();

        $this->postJson('/api/v1/agente/auth/login', [
            'email'    => $datos['user']->email,
            'password' => $datos['password'],
        ])
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.rol', RolSistema::AgenteParqueo->value)
            ->assertJsonStructure(['datos' => ['token', 'tipo_token', 'rol', 'agente' => ['id', 'codigo', 'estado']]]);
    }

    public function test_login_agente_rechaza_credenciales_invalidas(): void
    {
        $datos = $this->crearAgenteActivo();

        $this->postJson('/api/v1/agente/auth/login', [
            'email'    => $datos['user']->email,
            'password' => 'clave_incorrecta',
        ])
            ->assertStatus(401)
            ->assertJsonPath('exito', false);
    }

    public function test_login_agente_rechaza_usuario_conductor(): void
    {
        // Un conductor no puede iniciar sesión como agente
        $this->postJson('/api/v1/registro', [
            'cedula'                => '0503652349',
            'nombres'               => 'Juan',
            'apellidos'             => 'Pérez',
            'email'                 => 'conductor.falso@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'acepta_terminos'       => true,
        ]);

        $this->postJson('/api/v1/agente/auth/login', [
            'email'    => 'conductor.falso@example.com',
            'password' => 'password',
        ])
            ->assertStatus(403)
            ->assertJsonPath('exito', false);
    }

    public function test_login_agente_rechaza_agente_no_activo(): void
    {
        $datos = $this->crearAgenteActivo(['estado' => AgenteParqueo::ESTADO_SUSPENDIDO]);

        $this->postJson('/api/v1/agente/auth/login', [
            'email'    => $datos['user']->email,
            'password' => $datos['password'],
        ])
            ->assertStatus(403)
            ->assertJsonPath('exito', false);
    }

    public function test_perfil_agente_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/agente/auth/perfil')
            ->assertStatus(401);
    }

    public function test_perfil_agente_devuelve_datos_del_agente_autenticado(): void
    {
        $token = $this->tokenDeNuevoAgente();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/agente/auth/perfil')
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonStructure(['datos' => ['id', 'codigo', 'estado', 'nombre']]);
    }

    public function test_logout_agente_revoca_el_token(): void
    {
        $token = $this->tokenDeNuevoAgente();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/agente/auth/logout')
            ->assertOk()
            ->assertJsonPath('exito', true);

        // El token revocado ya no autoriza
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/agente/auth/perfil')
            ->assertStatus(401);
    }
}
