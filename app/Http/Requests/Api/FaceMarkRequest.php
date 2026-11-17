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
            'face_descriptor'   => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
            'request_id'        => 'required|string|max:100',
        ];
    }
}