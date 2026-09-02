<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollExclusion;
use Illuminate\Validation\Rule;

class StorePayrollExclusionRequest extends StoreRequest
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
            'concept' => [
                'required',
                'string',
                Rule::in(PayrollExclusion::CONCEPTS),
                Rule::unique('gh_payroll_exclusions')->where(function ($query) {
                    return $query->where('worker_id', $this->input('worker_id'))
                        ->where('period_id', $this->input('period_id'));
                }),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
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
            'concept.required' => 'El concepto es requerido',
            'concept.in' => 'El concepto seleccionado no es válido',
            'concept.unique' => 'Ya existe una exclusión de este concepto para ese trabajador y periodo',
            'reason.max' => 'El motivo no debe exceder 255 caracteres',
        ];
    }
}
