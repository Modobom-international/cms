<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushSystemSettingRequest extends FormRequest
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
            'ip' => 'string|max:255',
            'keyword_dtac' => 'string|max:255',
            'keyword_ais' => 'string|max:255',
            'share_web' => 'string|max:255',
            'link_web' => 'string|max:255',
            'domain' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'ip.max' => 'Trường ip không được vượt quá 255 ký tự.',
            'ip.string' => 'Trường ip phải là chuỗi.',
            'keyword_dtac.max' => 'Trường keyword_dtac không được vượt quá 255 ký tự.',
            'keyword_dtac.string' => 'Trường keyword_dtac phải là chuỗi.',
            'keyword_ais.max' => 'Trường keyword_ais không được vượt quá 255 ký tự.',
            'keyword_ais.string' => 'Trường keyword_ais phải là chuỗi.',
            'share_web.max' => 'Trường share_web không được vượt quá 255 ký tự.',
            'share_web.string' => 'Trường share_web phải là chuỗi.',
            'link_web.max' => 'Trường link_web không được vượt quá 255 ký tự.',
            'link_web.string' => 'Trường link_web phải là chuỗi.',
            'domain.max' => 'Trường domain không được vượt quá 255 ký tự.',
            'domain.string' => 'Trường domain phải là chuỗi.'
        ];
    }
}
