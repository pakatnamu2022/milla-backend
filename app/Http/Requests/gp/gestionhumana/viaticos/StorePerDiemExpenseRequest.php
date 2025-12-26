<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class StorePerDiemExpenseRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'expense_type_id' => ['required', 'integer', 'exists:gh_expense_type,id'],
      'expense_date' => ['required', 'date'],
      'receipt_amount' => ['required', 'numeric', 'min:0'],
      'receipt_type' => ['required', 'string', 'in:invoice,ticket,no_receipt'],
      'receipt_number' => ['required_if:receipt_type,invoice,ticket', 'nullable', 'string', 'max:255'],
      'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
      'notes' => ['nullable', 'string'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function attributes()
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
