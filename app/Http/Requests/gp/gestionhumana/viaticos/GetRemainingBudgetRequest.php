<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class GetRemainingBudgetRequest extends StoreRequest
{
  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      'expense_type_id' => ['required', 'integer', 'exists:gh_expense_type,id'],
      'date' => ['required', 'date', 'date_format:Y-m-d'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'expense_type_id.required' => 'El tipo de gasto es requerido',
      'expense_type_id.integer' => 'El tipo de gasto debe ser un número entero',
      'expense_type_id.exists' => 'El tipo de gasto seleccionado no existe',
      'date.required' => 'La fecha es requerida',
      'date.date' => 'La fecha debe ser una fecha válida',
      'date.date_format' => 'La fecha debe tener el formato YYYY-MM-DD',
    ];
  }
}
