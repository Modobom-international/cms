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
            'slug' => [
                'required',
                'string',
                Rule::unique('pages')->where(function ($query) {
                    return $query->where('site_id', $this->site_id);
                })
            ],
            'content' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'site_id.required' => __('validation.required'),
            'name.required' => __('validation.required'),
            'slug.required' => __('validation.required'),
            'slug.unique' => 'This slug is already in use within this site',
            'content.required' => __('validation.required'),
        ];
    }
}
