<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LabelRequest extends FormRequest
{
    public function authorize()
    {
        return true; //
    }
    
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
    
            'color' => 'required', 'string',
            ];
    }
    
    public function messages()
    {
        return [
            'name.required' => 'Tên label là bắt buộc.',
            'color.regex' => 'Màu sắc phải là mã màu HEX hợp lệ.',
        ];
    }
}

