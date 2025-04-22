<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DomainRequest extends FormRequest
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
            'domain' => 'required|string|max:255',
            'time_expired' => 'datetime',
            'registrar' => 'required|string|max:255',
            'renewable' => 'required',
            'status' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'Tên miền không được để trống',
            'domain.string' => 'Tên miền phải là một chuỗi',
            'domain.max' => 'Tên miền không được vượt quá 255 ký tự',
            'time_expired.datetime' => 'Thời gian hết hạn phải là ngày tháng',
            'registrar.required' => 'Nhà cung cấp không được để trống',
            'registrar.string' => 'Nhà cung cấp phải là một chuỗi',
            'registrar.max' => 'Nhà cung cấp không được vượt quá 255 ký tự',
            'renewable.required' => 'Trạng thái tự động đăng ký không được để trống',
            'status.required' => 'Trạng thái không được để trống',
        ];
    }
}
