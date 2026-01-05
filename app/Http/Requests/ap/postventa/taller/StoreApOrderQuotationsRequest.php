<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreApOrderQuotationsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'area_id' => ['required', 'integer', 'exists:ap_post_venta_masters,id'],
      'vehicle_id' => ['required', 'integer', 'exists:ap_vehicles,id'],
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
      'quotation_date' => ['required', 'date'],
      'expiration_date' => ['nullable', 'date', 'after_or_equal:quotation_date'],
      'observations' => ['nullable', 'string'],
    ];
  }

  public function messages(): array
  {
    return [
      'area_id.required' => 'Área de postventa es obligatoria.',
      'area_id.exists' => 'El área de postventa no existe.',
      'vehicle_id.required' => 'Vehículo asociado es obligatorio.',
      'vehicle_id.exists' => 'El vehículo asociado no existe.',
      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.exists' => 'La sede no existe.',
      'total_amount.required' => 'El total es obligatorio.',
      'quotation_date.required' => 'La fecha de cotización es obligatoria.',
      'expiration_date.after_or_equal' => 'La fecha de expiración debe ser posterior o igual a la fecha de cotización.',
    ];
  }
}
