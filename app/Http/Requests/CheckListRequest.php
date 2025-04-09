<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckListRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'card_id' => 'required',
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => __('validation.required'),
            'title.string' => __('validation.string'),
            'title.max' => __('validation.max'),
            'card_id.required' => __('validation.required'),
        ];
    }
}
