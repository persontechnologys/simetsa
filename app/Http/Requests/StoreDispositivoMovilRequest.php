<?php

// app/Http/Requests/StoreDispositivoMovilRequest.php

namespace App\Http\Requests;

use App\Models\DispositivoMovil;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para registrar o actualizar el token FCM de un dispositivo móvil.
 */
class StoreDispositivoMovilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token_fcm'          => ['required', 'string', 'min:10', 'max:512'],
            'plataforma'         => ['required', 'string', 'in:' . implode(',', DispositivoMovil::plataformas())],
            'canal'              => ['nullable', 'string', 'in:' . implode(',', DispositivoMovil::canales())],
            'tipo_app'           => ['nullable', 'string', 'in:conductor,agente'],
            'modelo_dispositivo' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token_fcm.required'  => 'El token FCM es obligatorio.',
            'plataforma.required' => 'Debe indicar la plataforma (ios o android).',
            'plataforma.in'       => 'La plataforma debe ser ios o android.',
            'canal.in'            => 'El canal debe ser fcm o web.',
            'tipo_app.in'         => 'El tipo de app debe ser conductor o agente.',
        ];
    }
}
