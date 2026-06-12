<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\IndexRequest;

class IndexPayrollRegisterRequest extends IndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'period_id' => 'nullable|integer',
            'worker_id' => 'nullable|integer',
            'status'    => 'nullable|string',
        ]);
    }
}
