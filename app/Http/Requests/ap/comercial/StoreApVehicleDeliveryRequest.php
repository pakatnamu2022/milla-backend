<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreApVehicleDeliveryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id'
      ],
      'vehicle_id' => [
        'required',
        'integer',
        'exists:ap_vehicles,id'
      ],
      'scheduled_delivery_date' => [
        'required',
        'date',
      ],
      'wash_date' => [
        'nullable',
        'date',
      ],
      'observations' => [
        'nullable',
        'string',
        'max:500',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.integer' => 'La sede debe ser un número entero.',
      'sede_id.exists' => 'La sede no existe.',
      'vehicle_id.required' => 'El vehículo es obligatorio.',
      'vehicle_id.integer' => 'El vehículo debe ser un número entero.',
      'vehicle_id.exists' => 'El vehículo no existe.',
      'scheduled_delivery_date.required' => 'La fecha de entrega programada es obligatoria.',
      'scheduled_delivery_date.date' => 'La fecha de entrega programada no es una fecha válida.',
      'wash_date.date' => 'La fecha de lavado no es una fecha válida.',
      'actual_delivery_date.date' => 'La fecha de entrega real no es una fecha válida.',
      'observations.string' => 'Las observaciones deben ser una cadena de texto.',
      'observations.max' => 'Las observaciones no deben exceder los 500 caracteres.',
    ];
  }
}
