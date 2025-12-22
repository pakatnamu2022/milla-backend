<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdatePerDiemRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'company_id' => ['nullable', 'integer', 'exists:companies,id'],
      'company_service_id' => ['nullable', 'integer', 'exists:companies,id'],
      'district_id' => ['nullable', 'integer', 'exists:district,id'],
      'start_date' => ['nullable', 'date'],
      'end_date' => ['nullable', 'date', 'after:start_date'],
      'purpose' => ['nullable', 'string', 'max:500'],
      'notes' => ['nullable', 'string', 'max:500'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'company_id.exists' => 'La empresa seleccionada no existe.',
      'company_service_id.exists' => 'El servicio de la empresa seleccionado no existe.',
      'district_id.exists' => 'El distrito seleccionado no existe.',
      'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
      'purpose.max' => 'El propósito no puede tener más de 500 caracteres.',
      'notes.max' => 'Las notas no pueden tener más de 500 caracteres.',
    ];
  }

  /**
   * Get the validated data with additional computed fields
   */
  public function validated($key = null, $default = null)
  {
    $data = parent::validated($key, $default);

    // Calculate total budget if budgets are provided
    if (isset($data['budgets']) && is_array($data['budgets'])) {
      $totalBudget = 0;
      foreach ($data['budgets'] as $budget) {
        $totalBudget += $budget['total'];
      }
      $data['total_budget'] = $totalBudget;
    }

    return $data;
  }
}
