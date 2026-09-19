<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollSubsidy;
use Illuminate\Validation\Rule;

class StorePayrollSubsidyRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
            'type' => ['nullable', 'string', Rule::in(PayrollSubsidy::TYPES)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            // Si no se envía, se estima con el promedio de los 12 meses anteriores
            // (PayrollSubsidyService::estimateAmount).
            'amount' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required' => 'El trabajador es requerido',
            'worker_id.exists' => 'El trabajador seleccionado no existe',
            'type.in' => 'El tipo de subsidio no es válido',
            'start_date.required' => 'La fecha de inicio es requerida',
            'start_date.date' => 'La fecha de inicio no es válida',
            'end_date.required' => 'La fecha de fin es requerida',
            'end_date.date' => 'La fecha de fin no es válida',
            'end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.min' => 'El monto no puede ser negativo',
            'reference.max' => 'La referencia no debe exceder 100 caracteres',
            'notes.max' => 'Las notas no deben exceder 255 caracteres',
        ];
    }
}
