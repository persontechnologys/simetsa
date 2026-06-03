<?php

// tests/Feature/Api/DispositivoMovilApiTest.php

namespace Tests\Feature\Api;

use App\Models\DispositivoMovil;
use App\Models\NotificacionPush;
use App\Models\User;
use App\Services\NotificacionPushService;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de endpoints de dispositivos móviles FCM y NotificacionPushService (Fase 5.G).
 *
 * En Fase 5 solo se persiste el token y se encolan notificaciones sin envío real.
 */
class DispositivoMovilApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private const TOKEN_EJEMPLO = 'fcm_token_ejemplo_123456789abcdef';

    // ────────────────────────────────────────────────────────────────────────
    // Registrar token FCM
    // ────────────────────────────────────────────────────────────────────────

    public function test_registrar_token_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/dispositivos', [])->assertUnauthorized();
    }

    public function test_conductor_puede_registrar_token_fcm(): void
    {
        $user = $this->conductorUser();

        $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [
                'token_fcm'  => self::TOKEN_EJEMPLO,
                'plataforma' => 'android',
            ])
            ->assertCreated()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.plataforma', 'android')
            ->assertJsonPath('datos.canal', 'fcm'); // default cuando no se envía canal

        $this->assertDatabaseHas('dispositivos_moviles', [
            'user_id'    => $user->id,
            'token_fcm'  => self::TOKEN_EJEMPLO,
            'plataforma' => 'android',
            'canal'      => 'fcm',
            'activo'     => true,
        ]);
    }

    public function test_registrar_token_con_metadatos_fcm_directo(): void
    {
        $user = $this->conductorUser();

        $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [
                'token_fcm'          => self::TOKEN_EJEMPLO,
                'plataforma'         => 'android',
                'canal'              => 'fcm',
                'tipo_app'           => 'conductor',
                'modelo_dispositivo' => 'Pixel 6',
            ])
            ->assertCreated()
            ->assertJsonPath('datos.canal', 'fcm')
            ->assertJsonPath('datos.tipo_app', 'conductor')
            ->assertJsonPath('datos.modelo_dispositivo', 'Pixel 6');

        $this->assertDatabaseHas('dispositivos_moviles', [
            'user_id'            => $user->id,
            'token_fcm'          => self::TOKEN_EJEMPLO,
            'canal'              => 'fcm',
            'tipo_app'           => 'conductor',
            'modelo_dispositivo' => 'Pixel 6',
        ]);
    }

    public function test_tipo_app_invalido_falla_validacion(): void
    {
        $user = $this->conductorUser();

        $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [
                'token_fcm'  => self::TOKEN_EJEMPLO,
                'plataforma' => 'android',
                'tipo_app'   => 'super_admin', // inválido
            ])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    public function test_canal_invalido_falla_validacion(): void
    {
        $user = $this->conductorUser();

        $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [
                'token_fcm'  => self::TOKEN_EJEMPLO,
                'plataforma' => 'android',
                'canal'      => 'expo_push', // inválido
            ])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    public function test_registrar_mismo_token_es_idempotente(): void
    {
        $user = $this->conductorUser();

        // Primera llamada → 201
        $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [
                'token_fcm'  => self::TOKEN_EJEMPLO,
                'plataforma' => 'android',
            ])
            ->assertCreated();

        // Segunda llamada con el mismo token → 200 (no duplica)
        $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [
                'token_fcm'  => self::TOKEN_EJEMPLO,
                'plataforma' => 'ios', // cambia la plataforma
            ])
            ->assertOk()
            ->assertJsonPath('datos.plataforma', 'ios');

        // Solo debe existir un registro
        $this->assertDatabaseCount('dispositivos_moviles', 1);
    }

    public function test_registrar_token_con_plataforma_invalida_falla_validacion(): void
    {
        $user = $this->conductorUser();

        $response = $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [
                'token_fcm'  => self::TOKEN_EJEMPLO,
                'plataforma' => 'windows', // inválida
            ])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);

        // El envelope SIMETSA usa 'errores' en lugar de 'errors'
        $this->assertArrayHasKey('plataforma', $response->json('errores'));
    }

    public function test_registrar_token_campos_vacios_falla_validacion(): void
    {
        $user = $this->conductorUser();

        $response = $this->withToken($this->token($user))
            ->postJson('/api/v1/dispositivos', [])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);

        $errores = $response->json('errores');
        $this->assertArrayHasKey('token_fcm', $errores);
        $this->assertArrayHasKey('plataforma', $errores);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Eliminar token FCM
    // ────────────────────────────────────────────────────────────────────────

    public function test_conductor_puede_eliminar_su_token(): void
    {
        $user = $this->conductorUser();

        // Crear el dispositivo directamente
        DispositivoMovil::create([
            'user_id'    => $user->id,
            'token_fcm'  => self::TOKEN_EJEMPLO,
            'plataforma' => 'android',
            'activo'     => true,
        ]);

        $this->withToken($this->token($user))
            ->deleteJson('/api/v1/dispositivos/' . self::TOKEN_EJEMPLO)
            ->assertOk()
            ->assertJsonPath('exito', true);

        $this->assertDatabaseMissing('dispositivos_moviles', [
            'user_id'   => $user->id,
            'token_fcm' => self::TOKEN_EJEMPLO,
        ]);
    }

    public function test_eliminar_token_inexistente_devuelve_404(): void
    {
        $user = $this->conductorUser();

        $this->withToken($this->token($user))
            ->deleteJson('/api/v1/dispositivos/token_que_no_existe')
            ->assertNotFound()
            ->assertJsonPath('exito', false);
    }

    public function test_no_puede_eliminar_token_de_otro_usuario(): void
    {
        $user1 = $this->conductorUser();

        // Crear un segundo usuario
        $user2 = User::factory()->create();
        $user2->assignRole('conductor');

        // Dispositivo registrado por user2
        DispositivoMovil::create([
            'user_id'    => $user2->id,
            'token_fcm'  => self::TOKEN_EJEMPLO,
            'plataforma' => 'ios',
            'activo'     => true,
        ]);

        // user1 intenta eliminar token de user2 → 404 (no existe para user1)
        $this->withToken($this->token($user1))
            ->deleteJson('/api/v1/dispositivos/' . self::TOKEN_EJEMPLO)
            ->assertNotFound();

        // El dispositivo de user2 sigue existiendo
        $this->assertDatabaseHas('dispositivos_moviles', [
            'user_id'   => $user2->id,
            'token_fcm' => self::TOKEN_EJEMPLO,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // NotificacionPushService
    // ────────────────────────────────────────────────────────────────────────

    public function test_encolar_notificacion_persiste_en_base_de_datos(): void
    {
        $user    = $this->conductorUser();
        $service = app(NotificacionPushService::class);

        $notif = $service->encolar(
            $user,
            NotificacionPush::TIPO_EXPIRA_PRONTO,
            ['titulo' => 'Tu ticket expira pronto', 'cuerpo' => 'Tienes 10 min restantes.'],
        );

        $this->assertInstanceOf(NotificacionPush::class, $notif);
        $this->assertFalse($notif->enviada);
        $this->assertEquals(NotificacionPush::TIPO_EXPIRA_PRONTO, $notif->tipo);

        $this->assertDatabaseHas('notificaciones_push', [
            'user_id' => $user->id,
            'tipo'    => NotificacionPush::TIPO_EXPIRA_PRONTO,
            'enviada' => false,
        ]);
    }

    public function test_marcar_enviada_actualiza_estado_y_timestamp(): void
    {
        $user    = $this->conductorUser();
        $service = app(NotificacionPushService::class);

        $notif = $service->encolar(
            $user,
            NotificacionPush::TIPO_ANULADO,
            ['titulo' => 'Ticket anulado', 'cuerpo' => 'Tu ticket fue anulado.'],
        );

        $this->assertFalse($notif->enviada);

        $actualizada = $service->marcarEnviada($notif);

        $this->assertTrue($actualizada->enviada);
        $this->assertNotNull($actualizada->enviada_en);
    }
}
