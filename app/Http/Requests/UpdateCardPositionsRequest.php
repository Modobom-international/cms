<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardPositionsRequest extends FormRequest
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
            'positions' => 'required|array',
            'positions.*.id' => 'required|exists:cards,id',
            'positions.*.position' => 'required|integer|min:0',
            'positions.*.list_id' => 'required|exists:lists,id'
        ];
    }

    public function messages()
    {
        return [
            'positions.required' => 'Positions array is required',
            'positions.array' => 'Positions must be an array',
            'positions.*.id.required' => 'Card ID is required for each position update',
            'positions.*.id.exists' => 'Card ID must exist in the database',
            'positions.*.position.required' => 'Position is required for each card',
            'positions.*.position.integer' => 'Position must be an integer',
            'positions.*.position.min' => 'Position must be at least 0',
            'positions.*.list_id.required' => 'List ID is required for each card',
            'positions.*.list_id.exists' => 'List ID must exist in the database'
        ];
    }
}