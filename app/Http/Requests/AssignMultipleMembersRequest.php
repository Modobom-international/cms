<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignMultipleMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'user_ids' => 'required|array',
        ];
    }
    
    public function messages(): array
    {
        return [
            'user_ids.required' => __('validation.required'),
        ];
    }
}
