<?php

namespace App\Http\Requests\ap\compras;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePurchaseReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approved' => 'required|boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'approved.required' => 'Debe indicar si aprueba o rechaza la recepción.',
            'approved.boolean' => 'El valor de aprobación debe ser verdadero o falso.',
        ];
    }
}