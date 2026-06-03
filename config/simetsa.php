<?php
// config/simetsa.php
// Configuración global del sistema SIMETSA — GAD Municipal del Cantón Salcedo.

use App\Enums\RolSistema;

return [
    /*
    |--------------------------------------------------------------------------
    | Roles habilitados para la app móvil
    |--------------------------------------------------------------------------
    | Solo los roles listados aquí pueden iniciar sesión desde la app Expo.
    | Para habilitar un nuevo rol (ej. comisario) basta con agregarlo a este
    | array — el MovilAuthController lo detecta automáticamente sin cambios
    | en el controlador.
    |
    */
    'roles_movil' => [
        RolSistema::Conductor->value,     // 'conductor'
        RolSistema::AgenteParqueo->value, // 'agente_parqueo'
    ],
];
