<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use Illuminate\Foundation\Http\FormRequest;

class ImportPayrollBonusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'company_id' => 'required|integer|exists:companies,id',
            'type_id' => 'required|integer|exists:gp_masters,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo Excel es requerido',
            'file.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls)',
            'file.max' => 'El archivo no debe superar los 10MB',
            'company_id.required' => 'La empresa es requerida',
            'company_id.exists' => 'La empresa seleccionada no existe',
            'type_id.required' => 'El tipo de bono es requerido',
            'type_id.exists' => 'El tipo de bono seleccionado no existe',
        ];
    }
}
