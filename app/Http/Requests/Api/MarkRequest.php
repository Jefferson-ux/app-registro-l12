<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_code' => 'required|string',
            'type' => [
                'required',
                'string',
                'in:ENTRY,EXIT,BREAK_START,BREAK_END',
            ],
            #'request_id' => ['required', 'uuid'],
        ];
    }
}
