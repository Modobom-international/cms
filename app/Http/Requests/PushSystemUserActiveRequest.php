<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushSystemUserActiveRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'token.max' => 'Trường token không được vượt quá 255 ký tự.',
            'token.string' => 'Trường token phải là chuỗi.',
            'token.required' => 'Trường token không được để trống.',
            'country.max' => 'Trường country không được vượt quá 255 ký tự.',
            'country.string' => 'Trường country phải là chuỗi.',
            'country.string' => 'Trường country không được để trống.',
        ];
    }
}
