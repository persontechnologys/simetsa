<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenPagoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /** La autorización se maneja en el middleware del controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'infraccion_id' => ['required', 'integer', 'exists:infracciones,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'infraccion_id.required' => 'El ID de la infracción es obligatorio.',
            'infraccion_id.exists'   => 'La infracción indicada no existe.',
        ];
    }
}
