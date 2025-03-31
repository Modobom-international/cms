<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
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
            'site_id' => 'required|integer',
            'name' => 'required|string',
            'slug' => 'required|string|unique:pages,slug',
            'content' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'site_id.required' => __('validation.required'),
            'name.required' => __('validation.required'),
            'slug.required' => __('validation.required'),
            'content.required' => __('validation.required'),
            'slug.unique' => __('validation.unique'),
        ];
    }
}
