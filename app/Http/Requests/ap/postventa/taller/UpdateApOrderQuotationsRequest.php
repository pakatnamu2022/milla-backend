<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateApOrderQuotationsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_order_id' => ['sometimes', 'required', 'integer', 'exists:ap_work_orders,id'],
      'currency_id' => ['sometimes', 'required', 'integer', 'exists:type_currency,id'],
      'area_id' => ['sometimes', 'integer', 'exists:ap_masters,id'],
      'vehicle_id' => ['sometimes', 'required', 'integer', 'exists:ap_vehicles,id'],
      'sede_id' => ['sometimes', 'required', 'integer', 'exists:config_sede,id'],
      'client_id' => ['sometimes', 'required', 'integer', 'exists:business_partners,id'],
      'quotation_date' => ['required', 'date'],
      'expiration_date' => ['nullable', 'date', 'after_or_equal:quotation_date'],
      'observations' => ['nullable', 'string'],
    ];
  }

  public function messages(): array
  {
    return [
      'work_order_id.required' => 'La orden de trabajo es obligatoria.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no es válida.',
      'currency_id.required' => 'Moneda es obligatoria.',
      'currency_id.exists' => 'La moneda no existe.',
      'area_id.exists' => 'El área de postventa no existe.',
      'vehicle_id.required' => 'Vehículo asociado es obligatorio.',
      'vehicle_id.exists' => 'El vehículo asociado no existe.',
      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.exists' => 'La sede no existe.',
      'client_id.required' => 'El cliente es obligatorio.',
      'client_id.exists' => 'El cliente no existe.',
      'quotation_date.required' => 'La fecha de cotización es obligatoria.',
      'expiration_date.after_or_equal' => 'La fecha de expiración debe ser posterior o igual a la fecha de cotización.',
    ];
  }
}
