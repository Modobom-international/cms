<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DueDateRequest extends FormRequest
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
            'start_date'    => 'nullable|date|before_or_equal:due_date',
            'due_date'      => 'required|date',
            'due_reminder' => 'nullable|integer|in:0,5,10,15,30,60,120,1440,2880', // minutes
        ];
    }
    
    public function messages()
    {
        return [
            'start_date.nullable' => __('validation.nullable'),
            'start_date.date' => __('validation.date'),
            'start_date.before_or_equal' => __('validation.before_or_equal'),
            'due_date.required' => __('validation.required'),
            'due_date.date' => __('validation.date'),
            'due_reminder.nullable' => __('validation.nullable'),
            'due_reminder.integer' => __('validation.integer'),
            'due_reminder.in' => __('validation.in'),
        ];
    }
}
