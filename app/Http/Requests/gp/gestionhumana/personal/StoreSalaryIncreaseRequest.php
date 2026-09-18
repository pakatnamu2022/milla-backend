<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use App\Http\Requests\StoreRequest;

class StoreSalaryIncreaseRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
            'new_salary' => ['required', 'numeric', 'min:0.01'],
            'effective_date' => ['required', 'date'],
            'previous_salary' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required' => 'El trabajador es requerido',
            'worker_id.exists' => 'El trabajador seleccionado no existe',
            'new_salary.required' => 'El sueldo nuevo es requerido',
            'new_salary.min' => 'El sueldo nuevo debe ser mayor a cero',
            'effective_date.required' => 'La fecha efectiva del aumento es requerida',
            'effective_date.date' => 'La fecha efectiva no es válida',
            'reason.max' => 'El motivo no debe exceder 255 caracteres',
        ];
    }
}
