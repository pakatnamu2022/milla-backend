<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class StorePerDiemRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'company_id' => ['required', 'integer', 'exists:companies,id'],
      'sede_service_id' => ['required', 'integer', 'exists:config_sede,id'],
      'district_id' => ['required', 'integer', 'exists:district,id'],
      'start_date' => ['required', 'date'],
      'end_date' => ['required', 'date', 'after_or_equal:start_date'],
      'purpose' => ['required', 'string', 'max:500'],
      'notes' => ['nullable', 'string', 'max:500'],
      'with_active' => ['required', 'boolean'],
    ];
  }

  /**
   * Get the validated data with additional computed fields
   */
  public function validated($key = null, $default = null)
  {
    $data = parent::validated($key, $default);
    $data['with_request'] = false;
    return $data;
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function attributes(): array
  {
    return [
      'company_id' => 'empresa',
      'sede_service_id' => 'sede de servicio',
      'district_id' => 'distrito',
      'start_date' => 'fecha de inicio',
      'end_date' => 'fecha de fin',
      'purpose' => 'propósito',
      'notes' => 'notas',
      'with_active' => 'con activo',
    ];
  }

}
