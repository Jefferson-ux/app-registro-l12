<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autenticación general ya la gestiona el middleware Sanctum
    }

    public function rules(): array
    {
        return [
            'incident_type' => [
                'required',
                'string',
                'in:late,absence,early_leave,missing_check_in,missing_check_out,manual',
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'incident_type.required' => 'El tipo de incidencia es obligatorio.',
            'incident_type.in'       => 'El tipo de incidencia seleccionado no es válido.',
            'description.string'     => 'La descripción debe ser un texto válido.',
            'description.max'        => 'La descripción es de tamaño excesivo.',
        ];
    }
}
