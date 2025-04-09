<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushSystemRequest extends FormRequest
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
            'token' => 'string|max:255',
            'app' => 'string|max:255',
            'platform' => 'string|max:255',
            'device' => 'string|max:255',
            'country' => 'string|max:255',
            'keyword' => 'string|max:255',
            'shortcode' => 'string|max:255',
            'telcoid' => 'string|max:255',
            'network' => 'string|max:255',
            'permission' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'token.max' => 'Trường token không được vượt quá 255 ký tự.',
            'token.string' => 'Trường token phải là chuỗi.',
            'app.max' => 'Trường app không được vượt quá 255 ký tự.',
            'app.string' => 'Trường app phải là chuỗi.',
            'platform.max' => 'Trường platform không được vượt quá 255 ký tự.',
            'platform.string' => 'Trường platform phải là chuỗi.',
            'device.max' => 'Trường device không được vượt quá 255 ký tự.',
            'device.string' => 'Trường device phải là chuỗi.',
            'country.max' => 'Trường country không được vượt quá 255 ký tự.',
            'country.string' => 'Trường country phải là chuỗi.',
            'keyword.max' => 'Trường keyword không được vượt quá 255 ký tự.',
            'keyword.string' => 'Trường keyword phải là chuỗi.',
            'shortcode.max' => 'Trường shortcode không được vượt quá 255 ký tự.',
            'shortcode.string' => 'Trường shortcode phải là chuỗi.',
            'telcoid.max' => 'Trường telcoid không được vượt quá 255 ký tự.',
            'telcoid.string' => 'Trường telcoid phải là chuỗi.',
            'network.max' => 'Trường network không được vượt quá 255 ký tự.',
            'network.string' => 'Trường network phải là chuỗi.',
        ];
    }
}
