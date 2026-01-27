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
      'receipt_amount' => ['sometimes', 'required', 'numeric', 'min:1'],
      'receipt_type' => ['sometimes', 'required', 'string', 'in:invoice,ticket,no_receipt'],
      'receipt_number' => ['sometimes', 'required_if:receipt_type,invoice,ticket', 'nullable', 'string', 'max:255'],
      'receipt_file' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
      'notes' => ['sometimes', 'nullable', 'string'],
      'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
      'ruc' => ['sometimes', 'nullable', 'required_if:receipt_type,invoice', 'string', 'max:20'],
    ];
  }

  public function messages(): array
  {
    return [
      'expense_type_id.required' => 'El tipo de gasto es obligatorio.',
      'expense_type_id.integer' => 'El tipo de gasto debe ser un número entero.',
      'expense_type_id.exists' => 'El tipo de gasto seleccionado no es válido.',
      'expense_date.required' => 'La fecha del gasto es obligatoria.',
      'expense_date.date' => 'La fecha del gasto no es una fecha válida.',
      'receipt_amount.required' => 'El monto del comprobante es obligatorio.',
      'receipt_amount.numeric' => 'El monto del comprobante debe ser un número.',
      'receipt_amount.min' => 'El monto del comprobante debe ser al menos S/. 1',
      'receipt_type.required' => 'El tipo de comprobante es obligatorio.',
      'receipt_type.string' => 'El tipo de comprobante debe ser una cadena de texto.',
      'receipt_type.in' => 'El tipo de comprobante seleccionado no es válido.',
      'receipt_number.required_if' => 'El número de comprobante es obligatorio cuando el tipo de comprobante es factura o ticket.',
      'reason.string' => 'El motivo debe ser una cadena de texto.',
      'reason.max' => 'El motivo no debe exceder los 500 caracteres.',
      'ruc.required_if' => 'El RUC es obligatorio cuando el tipo de comprobante es factura.',
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function attributes(): array
  {
    return [
      'expense_type_id' => 'tipo de gasto',
      'expense_date' => 'fecha del gasto',
      'concept' => 'concepto',
      'receipt_amount' => 'monto del comprobante',
      'company_amount' => 'monto a cargo de la empresa',
      'employee_amount' => 'monto a cargo del colaborador',
      'receipt_type' => 'tipo de comprobante',
      'receipt_number' => 'número de comprobante',
      'receipt_file' => 'archivo del comprobante',
      'notes' => 'observaciones',
      'ruc' => 'RUC',
    ];
  }
}
