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
            'settlement_date' => ['required', 'date'],
            'total_spent' => ['required', 'numeric', 'min:0'],
            'balance_to_return' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'settlement_date.required' => 'La fecha de liquidación es requerida.',
            'settlement_date.date' => 'La fecha de liquidación debe ser una fecha válida.',
            'total_spent.required' => 'El total gastado es requerido.',
            'total_spent.numeric' => 'El total gastado debe ser un número.',
            'total_spent.min' => 'El total gastado debe ser mayor o igual a 0.',
            'balance_to_return.required' => 'El saldo a devolver es requerido.',
            'balance_to_return.numeric' => 'El saldo a devolver debe ser un número.',
            'balance_to_return.min' => 'El saldo a devolver debe ser mayor o igual a 0.',
        ];
    }

    /**
     * Get the validated data with additional computed fields
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Set settled status and update request status
        $data['settled'] = true;
        $data['status'] = 'settled';

        return $data;
    }
}
