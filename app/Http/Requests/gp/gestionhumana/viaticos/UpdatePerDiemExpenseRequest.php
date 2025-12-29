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
      'ruc' => ['sometimes', 'nullable', 'required_if:receipt_type,invoice', 'string', 'max:20'],
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
