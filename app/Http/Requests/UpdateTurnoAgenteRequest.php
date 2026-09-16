<?php
// app/Http/Requests/UpdateTurnoAgenteRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para finalizar un turno de agente.
 */
class UpdateTurnoAgenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
