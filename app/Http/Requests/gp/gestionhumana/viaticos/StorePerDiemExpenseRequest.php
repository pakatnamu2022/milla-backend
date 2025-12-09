<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use Illuminate\Foundation\Http\FormRequest;

class StorePerDiemExpenseRequest extends FormRequest
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
            'expense_type_id' => ['required', 'integer', 'exists:gh_expense_type,id'],
            'expense_date' => ['required', 'date'],
            'concept' => ['required', 'string', 'max:255'],
            'receipt_amount' => ['required', 'numeric', 'min:0'],
            'company_amount' => ['required', 'numeric', 'min:0'],
            'employee_amount' => ['required', 'numeric', 'min:0'],
            'receipt_type' => ['required', 'string', 'in:invoice,ticket,no_receipt'],
            'receipt_number' => ['required_if:receipt_type,invoice,ticket', 'nullable', 'string', 'max:255'],
            'receipt_path' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
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
            'expense_type_id.required' => 'El tipo de gasto es requerido.',
            'expense_type_id.exists' => 'El tipo de gasto seleccionado no existe.',
            'expense_date.required' => 'La fecha del gasto es requerida.',
            'concept.required' => 'El concepto del gasto es requerido.',
            'receipt_amount.required' => 'El monto del recibo es requerido.',
            'company_amount.required' => 'El monto de la empresa es requerido.',
            'employee_amount.required' => 'El monto del empleado es requerido.',
            'receipt_type.required' => 'El tipo de comprobante es requerido.',
            'receipt_type.in' => 'El tipo de comprobante debe ser: factura, boleta o sin comprobante.',
            'receipt_number.required_if' => 'El número de comprobante es requerido cuando el tipo es factura o boleta.',
        ];
    }

    /**
     * Get the validated data with additional computed fields
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Set default validation fields
        $data['validated'] = false;
        $data['validated_by'] = null;
        $data['validated_at'] = null;

        return $data;
    }
}
