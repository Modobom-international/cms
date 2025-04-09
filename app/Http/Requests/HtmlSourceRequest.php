<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HtmlSourceRequest extends FormRequest
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
            'source' => 'required|string',
            'url' => 'required|string|url|regex:/youtube/i',
            'app_id' => 'max:255',
            'version' => 'max:255',
            'country' => 'max:191',
            'device_id' => 'max:191',
            'platform' => 'max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'source.required' => 'Trường source là bắt buộc.',
            'source.string' => 'Trường source phải là chuỗi.',
            'url.required' => 'Trường URL là bắt buộc.',
            'url.string' => 'Trường URL phải là chuỗi.',
            'url.url' => 'Trường URL phải là một địa chỉ hợp lệ.',
            'url.regex' => 'URL phải chứa chuỗi "youtube".',
            'app_id.max' => 'Trường app_id không được vượt quá 255 ký tự.',
            'version.max' => 'Trường version không được vượt quá 255 ký tự.',
            'country.max' => 'Trường country không được vượt quá 191 ký tự.',
            'device_id.max' => 'Trường device_id không được vượt quá 191 ký tự.',
            'platform.max' => 'Trường platform không được vượt quá 191 ký tự.',
        ];
    }
}
