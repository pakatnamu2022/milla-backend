<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StoreOrUpdatePayrollFoodCardRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => [
                'required',
                'integer',
                'exists:rrhh_persona,id',
            ],
            'period_id' => [
                'required',
                'integer',
                'exists:gh_payroll_periods,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'applies' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required' => 'El trabajador es requerido',
            'worker_id.integer' => 'El trabajador debe ser un número entero',
            'worker_id.exists' => 'El trabajador seleccionado no existe',
            'period_id.required' => 'El periodo es requerido',
            'period_id.integer' => 'El periodo debe ser un número entero',
            'period_id.exists' => 'El periodo seleccionado no existe',
            'amount.required' => 'El monto es requerido',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.min' => 'El monto debe ser mayor o igual a 0',
            'amount.max' => 'El monto no debe exceder 999,999,999.99',
            'applies.required' => 'El campo "aplica" es requerido',
            'applies.boolean' => 'El campo "aplica" debe ser verdadero o falso',
        ];
    }
}