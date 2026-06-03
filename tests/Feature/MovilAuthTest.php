<?php
// tests/Feature/MovilAuthTest.php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\AgenteParqueo;
use App\Models\Conductor;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests del endpoint de autenticación móvil unificada (Fase 9 refactor).
 *
 * Verifica que un único endpoint /api/v1/movil/login funcione para conductor,
 * agente, y rechace correctamente roles no habilitados, cuentas inactivas
 * y credenciales inválidas.
 */
class MovilAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private const EMAIL_CONDUCTOR = 'conductor.movil@test.com';
    private const EMAIL_AGENTE    = 'agente.movil@test.com';
    private const PASSWORD        = 'password';

    /**
     * Crea un conductor completo vía el endpoint de registro (crea User + rol + Conductor).
     * Evita depender del seeder que solo crea User sin el modelo Conductor.
     */
    private function crearConductor(array $overrides = []): array
    {
        $email = $overrides['email'] ?? self::EMAIL_CONDUCTOR;

        $this->postJson('/api/v1/registro', [
            'cedula'                => '0503652349',
            'nombres'               => 'Juan',
            'apellidos'             => 'Pérez',
            'email'                 => $email,
            'password'              => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'acepta_terminos'       => true,
        ]);

        $user      = User::where('email', $email)->firstOrFail();
        $conductor = Conductor::where('user_id', $user->id)->firstOrFail();

        return ['user' => $user, 'conductor' => $conductor];
    }

    /** Crea agente activo de prueba vinculado a un usuario con rol agente_parqueo. */
    private function crearAgenteActivo(array $overrides = []): array
    {
        $email = $overrides['email'] ?? self::EMAIL_AGENTE;

        $user = User::factory()->create([
            'email'    => $email,
            'password' => Hash::make($overrides['password'] ?? self::PASSWORD),
        ]);
        $user->assignRole(RolSistema::AgenteParqueo->value);

        $agente = AgenteParqueo::create([
            'user_id'                  => $user->id,
            'codigo'                   => 'AG-TEST',
            'numero_credencial'        => 'CRED-TEST',
            'carta_compromiso_firmada' => true,
            'fecha_autorizacion'       => now()->toDateString(),
            'estado'                   => $overrides['estado'] ?? AgenteParqueo::ESTADO_ACTIVO,
        ]);

        return ['user' => $user, 'agente' => $agente, 'password' => $overrides['password'] ?? self::PASSWORD];
    }

    private function tokenConductor(): string
    {
        $this->crearConductor();
        return $this->postJson('/api/v1/movil/login', [
            'email'    => self::EMAIL_CONDUCTOR,
            'password' => self::PASSWORD,
        ])->json('datos.token');
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_login_conductor_devuelve_token_usuario_roles_y_perfil_operativo(): void
    {
        $this->crearConductor();

        $this->postJson('/api/v1/movil/login', ['email' => self::EMAIL_CONDUCTOR, 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.tipo_token', 'Bearer')
            ->assertJsonPath('datos.usuario.roles.0', RolSistema::Conductor->value)
            ->assertJsonStructure(['datos' => [
                'token', 'tipo_token',
                'usuario'          => ['id', 'nombre', 'email', 'roles', 'permisos'],
                'perfil_operativo' => ['id', 'codigo', 'estado', 'nombre'],
            ]]);
    }

    public function test_login_agente_devuelve_token_usuario_roles_y_perfil_operativo(): void
    {
        $datos = $this->crearAgenteActivo();

        $this->postJson('/api/v1/movil/login', ['email' => $datos['user']->email, 'password' => $datos['password']])
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.usuario.roles.0', RolSistema::AgenteParqueo->value)
            ->assertJsonStructure(['datos' => [
                'token',
                'usuario'          => ['id', 'nombre', 'email', 'roles', 'permisos'],
                'perfil_operativo' => ['id', 'codigo', 'estado', 'nombre'],
            ]]);
    }

    public function test_login_rechaza_credenciales_invalidas(): void
    {
        $this->crearConductor();

        $this->postJson('/api/v1/movil/login', ['email' => self::EMAIL_CONDUCTOR, 'password' => 'clave_incorrecta'])
            ->assertStatus(401)
            ->assertJsonPath('exito', false);
    }

    public function test_login_rechaza_rol_no_habilitado_para_movil(): void
    {
        // super_admin no está en config('simetsa.roles_movil')
        $admin = User::where('email', 'admin@simetsa.gob.ec')->first();

        $this->postJson('/api/v1/movil/login', ['email' => $admin->email, 'password' => self::PASSWORD])
            ->assertStatus(403)
            ->assertJsonPath('exito', false);
    }

    public function test_login_rechaza_conductor_no_activo(): void
    {
        $datos     = $this->crearConductor();
        $conductor = $datos['conductor'];
        $conductor->update(['estado' => 'bloqueado']);

        $this->postJson('/api/v1/movil/login', ['email' => self::EMAIL_CONDUCTOR, 'password' => self::PASSWORD])
            ->assertStatus(403)
            ->assertJsonPath('exito', false);
    }

    public function test_login_rechaza_agente_no_activo(): void
    {
        $datos = $this->crearAgenteActivo(['estado' => AgenteParqueo::ESTADO_SUSPENDIDO]);

        $this->postJson('/api/v1/movil/login', ['email' => $datos['user']->email, 'password' => $datos['password']])
            ->assertStatus(403)
            ->assertJsonPath('exito', false);
    }

    public function test_me_devuelve_usuario_y_perfil_operativo_con_token_valido(): void
    {
        $token = $this->tokenConductor();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/movil/me')
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonStructure(['datos' => [
                'usuario'          => ['id', 'nombre', 'email', 'roles', 'permisos'],
                'perfil_operativo' => ['id', 'codigo', 'estado'],
            ]]);
    }

    public function test_logout_revoca_token_y_me_devuelve_401(): void
    {
        $token = $this->tokenConductor();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/movil/logout')
            ->assertOk()
            ->assertJsonPath('exito', true);

        // El token ya no es válido
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/movil/me')
            ->assertStatus(401);
    }
}
