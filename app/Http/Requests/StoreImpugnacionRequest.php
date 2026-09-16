<?php

// app/Http/Requests/StoreImpugnacionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload para presentar una impugnación (Art. 17.f).
 */
class StoreImpugnacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización por HasMiddleware en el controller.
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de la impugnación es obligatorio.',
            'motivo.min'      => 'El motivo debe tener al menos 20 caracteres.',
            'motivo.max'      => 'El motivo no puede superar los 2000 caracteres.',
        ];
    }
}
