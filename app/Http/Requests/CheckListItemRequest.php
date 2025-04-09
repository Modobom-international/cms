<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:255',
        ];
    }
    
    public function messages(): array
    {
        return [
            'content.required' => __('validation.required'),
            'content.string' => __('validation.string'),
            'content.max' => __('validation.max'),
        ];
    }
}
