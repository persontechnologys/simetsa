<?php

// app/Http/Requests/LiberarInmovilizacionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la liberación administrativa de un vehículo inmovilizado.
 *
 * El motivo es obligatorio para liberaciones desde el backoffice,
 * dado que el InfraccionService::liberar() requiere justificación cuando
 * la infracción no ha sido pagada (Art. 15 Ordenanza SIMETSA).
 */
class LiberarInmovilizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe indicar el motivo de la liberación administrativa.',
            'motivo.min'      => 'El motivo debe tener al menos 10 caracteres.',
        ];
    }
}
