<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'permissions' => 'array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên nhóm không được để trống',
            'name.string' => 'Tên nhóm phải là một chuỗi',
            'name.max' => 'Tên nhóm không được vượt quá 255 ký tự',
            'permissions.array' => 'Quyền phải là một mảng',
        ];
    }
}
