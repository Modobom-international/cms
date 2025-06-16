<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileRequest extends FormRequest
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
            'file' => 'required|file',
            'path' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Trường file là bắt buộc.',
            'file.file' => 'Không đúng định dạng của file.',
            'path.required' => 'Trường path là bắt buộc.',
            'path.string' => 'Không đúng định dạng của path.',
        ];
    }
}
