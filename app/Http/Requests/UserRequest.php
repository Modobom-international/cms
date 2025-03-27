<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:191|unique:users,email,' . $this->user()->id,
            'profile_photo_path' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
    
    public function messages()
    {
        return [
            'name.required' => __('validation.required'),
            'name.string' => __('validation.string'),
            'name.max' => __('validation.max'),
            'email.required' => __('validation.required'),
            'email.email' => __('validation.email'),
            'email.unique' => __('validation.unique'),
            'email.max' => __('validation.max'),
            'profile_photo_path.image' => __('Incorrect image format'),
            'profile_photo_path.mimes' => __('Incorrect image format'),
            'profile_photo_path.max' => __('Image size is maximum'),
        ];
    }
}
