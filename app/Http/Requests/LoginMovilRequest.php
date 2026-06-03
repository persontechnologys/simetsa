<?php
// app/Http/Requests/LoginMovilRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del inicio de sesión unificado para la app móvil (Fase 9 refactor).
 *
 * Se crea separado de LoginConductorRequest para poder extenderlo a futuro
 * (ej. agregar device_id, versión de app) sin afectar el flujo legacy.
 */
class LoginMovilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // endpoint público
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
