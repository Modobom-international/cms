<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServerRequest extends FormRequest
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
            'ip' => 'required|ip|unique:servers,ip',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên server không được để trống.',
            'ip.required' => 'Địa chỉ IP không được để trống.',
            'ip.ip' => 'Không đúng định dạng địa chỉ IP.',
            'ip.unique' => 'Địa chỉ IP đã tồn tại trong hệ thống.',
        ];
    }
}
