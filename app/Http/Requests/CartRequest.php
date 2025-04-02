<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
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
            'list_id' => 'required',
            'title' => 'required|string|max:255',
        ];
    }
    
    public function messages()
    {
        return [
            'list_id.required' => __('validation.required'),
            'title.required' => __('validation.required'),
            'title.string' => __('validation.string'),
            'title.max' => __('validation.max'),
        ];
    }
}
