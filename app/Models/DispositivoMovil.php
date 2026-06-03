<?php

// app/Models/DispositivoMovil.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dispositivo móvil registrado para recibir notificaciones push vía FCM.
 * La integración real con Firebase Cloud Messaging se activa en Fase 6.
 *
 * @property int                  $id
 * @property int                  $user_id
 * @property string               $token_fcm
 * @property string               $plataforma      ios | android
 * @property string               $canal           fcm | web
 * @property string|null          $tipo_app        conductor | agente
 * @property string|null          $modelo_dispositivo
 * @property bool                 $activo
 * @property \Carbon\Carbon|null  $ultimo_uso_at
 */
class DispositivoMovil extends Model
{
    use HasFactory;

    protected $table = 'dispositivos_moviles';

    public const PLATAFORMA_IOS     = 'ios';
    public const PLATAFORMA_ANDROID = 'android';

    /** Canal FCM directo (móvil nativo Android/iOS). */
    public const CANAL_FCM  = 'fcm';
    /** Canal Web Push API (futuro backoffice). */
    public const CANAL_WEB  = 'web';

    public const TIPO_APP_CONDUCTOR = 'conductor';
    public const TIPO_APP_AGENTE    = 'agente';

    protected $fillable = [
        'user_id', 'token_fcm', 'plataforma', 'canal',
        'tipo_app', 'modelo_dispositivo', 'activo', 'ultimo_uso_at',
    ];

    protected $casts = [
        'activo'        => 'boolean',
        'ultimo_uso_at' => 'datetime',
    ];

    /** Usuario propietario del dispositivo. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Plataformas válidas para la app móvil. */
    public static function plataformas(): array
    {
        return [self::PLATAFORMA_IOS, self::PLATAFORMA_ANDROID];
    }

    /** Canales de push válidos. */
    public static function canales(): array
    {
        return [self::CANAL_FCM, self::CANAL_WEB];
    }
}
