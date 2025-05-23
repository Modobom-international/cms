<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'board_id' => 'required',
            'title' => 'required|string|max:255',
            'position' => 'nullable|integer'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => __('validation.required'),
            'title.string' => __('validation.string'),
            'title.max' => __('validation.max'),
            'board_id.required' => __('validation.required'),
            'position.nullable' => __('validation.nullable'),
            'position.integer' => __('validation.integer'),
        ];
    }
}
