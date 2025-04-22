<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachmentRequest extends FormRequest
{
    public function authorize()
    {
        return true; // hoặc kiểm tra quyền ở đây nếu cần
    }
    
    public function rules()
    {
        return [
            'title'      => 'string|max:255',
            'file_path'      => 'required|file|max:10240', // 10MB
            'url'       => 'required|url',
        ];
    }
    
    public function messages()
    {
        return [
            'title.required' => __('validation.string'),
            'title.max' => __('validation.max'),
            'file_path.file' => __('validation.file'),
            'file_path.required' => __('validation.required'),
            'file_path.max' => __('validation.max'),
            'url.required' => __('validation.required'),
            'url.url' => __('validation.url'),
        ];
    }
}

