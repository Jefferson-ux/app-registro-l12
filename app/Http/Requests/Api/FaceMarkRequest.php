<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class FaceMarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
            'request_id' => 'required|string|max:100',
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'notes' => ['prohibited'],
        ];
    }
}
