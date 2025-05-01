<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackingScriptRequest extends FormRequest
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
            'tracking_script' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'tracking_script.required' => 'Tracking script is required',
            'tracking_script.string' => 'Tracking script must be a string',
        ];
    }
}
