<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdatePerDiemExpenseRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'expense_type_id' => ['sometimes', 'required', 'integer', 'exists:gh_expense_type,id'],
      'expense_date' => ['sometimes', 'required', 'date'],
      'concept' => ['sometimes', 'required', 'string', 'max:255'],
      'receipt_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
      'company_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
      'employee_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
      'receipt_type' => ['sometimes', 'required', 'string', 'in:invoice,ticket,no_receipt'],
      'receipt_number' => ['sometimes', 'required_if:receipt_type,invoice,ticket', 'nullable', 'string', 'max:255'],
      'receipt_file' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
      'notes' => ['sometimes', 'nullable', 'string'],
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
      'receipt_file.file' => 'El comprobante debe ser un archivo válido.',
      'receipt_file.mimes' => 'El comprobante debe ser un archivo PDF, JPG, JPEG o PNG.',
      'receipt_file.max' => 'El comprobante no debe superar los 10MB.',
    ];
  }
}
