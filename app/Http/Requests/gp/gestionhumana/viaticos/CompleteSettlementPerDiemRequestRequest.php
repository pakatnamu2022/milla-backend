<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use Illuminate\Foundation\Http\FormRequest;

class CompleteSettlementPerDiemRequestRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'comments.string' => 'Los comentarios deben ser texto.',
            'comments.max' => 'Los comentarios no pueden exceder 1000 caracteres.',
        ];
    }
}
