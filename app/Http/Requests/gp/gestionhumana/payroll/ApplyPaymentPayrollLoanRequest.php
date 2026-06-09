<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class ApplyPaymentPayrollLoanRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'scheduled_date'  => ['required', 'date'],
            'concept_type_id' => ['nullable', 'integer', 'exists:general_masters,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount'          => 'monto',
            'scheduled_date'  => 'fecha de pago',
            'concept_type_id' => 'tipo de concepto',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'         => 'El :attribute es obligatorio.',
            'amount.min'              => 'El :attribute debe ser mayor a 0.',
            'scheduled_date.required' => 'La :attribute es obligatoria.',
            'scheduled_date.date'     => 'La :attribute no tiene un formato válido.',
            'concept_type_id.exists'  => 'El :attribute seleccionado no existe.',
        ];
    }
}