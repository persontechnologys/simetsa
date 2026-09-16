<?php
// app/Http/Requests/StoreIncidenteCalleRequest.php

namespace App\Http\Requests;

use App\Enums\TipoIncidente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validación para registrar un incidente de calle (Art. 38.m).
 */
class StoreIncidenteCalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipo'           => ['required', new Enum(TipoIncidente::class)],
            'descripcion'    => ['required', 'string', 'max:1000'],
            'latitud'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitud'       => ['nullable', 'numeric', 'between:-180,180'],
            'foto_evidencia' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
