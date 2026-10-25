<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceRecordsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 1. Identificación del Empleado (Mapeamos por el código único)
            'employee_code'     => 'required|string|exists:employees,employee_code',

            // 2. Tipo de Marcación (Basado en tu ENUM de la BD)
            'type'              => 'required|string|in:check_in,check_out,break_start,break_end',

            // 3. Método de Marcación (Basado en tu ENUM de la BD)
            'method'            => 'required|string|in:web,mobile,kiosk,qr,manual,biometric,api',

            // 4. Ubicación Geográfica (GPS del Frontend)
            // Usamos 'numeric' para validar decimales y 'between' para los rangos reales del globo terráqueo
            'latitude'          => 'required|numeric|between:-90,90',
            'longitude'         => 'required|numeric|between:-180,180',

            // 5. Campos de entorno opcionales que el frontend podría forzar,
            // aunque recuerda que Laravel puede capturar la IP y el UserAgent por su cuenta
            'device_identifier' => 'nullable|string|max:255',
            'notes'             => 'nullable|string',

            // 6. UUID para evitar duplicados en peticiones repetidas (Idempotencia)
            'request_id'        => 'required|uuid',
        ];
    }

    /**
     * Personaliza los mensajes de error en español.
     */
    public function messages(): array
    {
        return [
            'employee_code.required' => 'El código de empleado es obligatorio.',
            'employee_code.exists'   => 'El código de empleado ingresado no existe en el sistema.',
            'type.required'          => 'El tipo de marcación (entrada, salida, etc.) es obligatorio.',
            'type.in'                => 'El tipo de marcación seleccionado no es válido.',
            'method.required'        => 'El método utilizado para marcar es obligatorio.',
            'method.in'              => 'El método de marcación seleccionado no es válido.',
            'latitude.required'      => 'La latitud de tu ubicación es obligatoria.',
            'latitude.numeric'       => 'La latitud debe ser un valor numérico decimal.',
            'latitude.between'       => 'La latitud está fuera del rango geográfico permitido.',
            'longitude.required'     => 'La longitud de tu ubicación es obligatoria.',
            'longitude.numeric'      => 'La longitud debe ser un valor numérico decimal.',
            'longitude.between'      => 'La longitud está fuera del rango geográfico permitido.',
            'request_id.required'    => 'El identificador único de la petición es obligatorio.',
            'request_id.uuid'        => 'El identificador de la petición debe ser un formato UUID válido.',
        ];
    }
}
