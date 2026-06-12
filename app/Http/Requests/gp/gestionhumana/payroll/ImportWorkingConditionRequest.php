<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use Illuminate\Foundation\Http\FormRequest;

class ImportWorkingConditionRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'period_id' => 'required|integer|exists:gh_payroll_periods,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'El archivo Excel es requerido',
            'file.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls)',
            'file.max' => 'El archivo no debe superar los 10MB',
            'period_id.required' => 'El periodo es requerido',
            'period_id.exists' => 'El periodo seleccionado no existe',
        ];
    }
}