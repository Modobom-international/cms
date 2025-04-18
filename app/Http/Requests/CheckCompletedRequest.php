<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckCompletedRequest extends FormRequest
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
            'is_completed' => 'required|integer|in:0,1',
           
        ];
    }
    
    public function messages()
    {
        return [
            'is_completed.required' => __('validation.required'),
            'is_completed.integer' => __('validation.integer'),
            'is_completed.in' => __('value không hợp lệ'),
        ];
    }
}
