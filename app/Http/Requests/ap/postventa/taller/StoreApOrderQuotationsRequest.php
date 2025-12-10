<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreApOrderQuotationsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'vehicle_id' => ['required', 'integer', 'exists:ap_vehicles,id'],
      'quotation_date' => ['required', 'date'],
      'expiration_date' => ['nullable', 'date', 'after_or_equal:quotation_date'],
      'observations' => ['nullable', 'string'],
    ];
  }

  public function messages(): array
  {
    return [
      'vehicle_id.required' => 'Vehículo asociado es obligatorio.',
      'vehicle_id.exists' => 'El vehículo asociado no existe.',
      'total_amount.required' => 'El total es obligatorio.',
      'quotation_date.required' => 'La fecha de cotización es obligatoria.',
      'expiration_date.after_or_equal' => 'La fecha de expiración debe ser posterior o igual a la fecha de cotización.',
    ];
  }
}
