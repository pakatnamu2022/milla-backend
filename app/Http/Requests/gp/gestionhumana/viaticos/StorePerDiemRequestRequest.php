<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class StorePerDiemRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'company_id' => ['required', 'integer', 'exists:companies,id'],
      'company_service_id' => ['required', 'integer', 'exists:companies,id'],
      'district_id' => ['required', 'integer', 'exists:district,id'],
      'start_date' => ['required', 'date'],
      'end_date' => ['required', 'date', 'after:start_date'],
      'purpose' => ['required', 'string', 'max:500'],
      'notes' => ['nullable', 'string', 'max:500'],
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
      'company_id.required' => 'La empresa es requerida.',
      'company_id.exists' => 'La empresa seleccionada no existe.',
      'company_service_id.required' => 'El servicio de la empresa es requerido.',
      'company_service_id.exists' => 'El servicio de la empresa seleccionado no existe.',
      'district_id.required' => 'El distrito es requerido.',
      'district_id.exists' => 'El distrito seleccionado no existe.',
      'start_date.required' => 'La fecha de inicio es requerida.',
      'end_date.required' => 'La fecha de fin es requerida.',
      'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
      'purpose.required' => 'El propósito es requerido.',
      'purpose.max' => 'El propósito no puede tener más de 500 caracteres.',
      'notes.max' => 'Las notas no pueden tener más de 500 caracteres.',
    ];
  }

}
