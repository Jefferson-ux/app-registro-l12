<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectKioskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_identifier' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_identifier.required' => 'El identificador del dispositivo es obligatorio.',
            'device_identifier.string' => 'El identificador del dispositivo debe ser texto.',
            'device_identifier.max' => 'El identificador del dispositivo no puede superar los 255 caracteres.',
            'latitude.numeric' => 'La latitud debe ser un número.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'latitude.required_with' => 'La latitud es obligatoria cuando se envía la longitud.',
            'longitude.numeric' => 'La longitud debe ser un número.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',
            'longitude.required_with' => 'La longitud es obligatoria cuando se envía la latitud.',
        ];
    }
}
