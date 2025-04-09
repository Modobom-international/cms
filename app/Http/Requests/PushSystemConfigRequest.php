<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushSystemConfigRequest extends FormRequest
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
            'data' => 'required|string',
            'share' => 'required|integer',
            'push_index' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => 'Trường data không được để trống.',
            'data.string' => 'Trường data phải là chuỗi.',
            'share.required' => 'Trường share không được để trống.',
            'share.integer' => 'Trường share phải là số.',
            'push_index.required' => 'Trường push_index không được để trống.',
        ];
    }
}
