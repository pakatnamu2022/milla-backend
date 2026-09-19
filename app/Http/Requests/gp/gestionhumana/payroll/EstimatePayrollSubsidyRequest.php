<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\IndexRequest;

class EstimatePayrollSubsidyRequest extends IndexRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
