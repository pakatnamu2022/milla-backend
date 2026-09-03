<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreAssetRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_vehicle_id' => [
        'required',
        'integer',
        'exists:ap_vehicles,id',
      ],
      'worker_id' => [
        'required',
        'integer',
      ],
      'assigned_date' => [
        'required',
        'date',
      ],
      'observation' => [
        'nullable',
        'string',
        'max:1000',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'ap_vehicle_id.required' => 'El vehículo es obligatorio.',
      'ap_vehicle_id.exists'   => 'El vehículo seleccionado no existe.',
      'worker_id.required'     => 'El trabajador es obligatorio.',
      'assigned_date.required' => 'La fecha de asignación es obligatoria.',
      'assigned_date.date'     => 'La fecha de asignación no es válida.',
      'observation.max'        => 'La observación no puede exceder los 1000 caracteres.',
    ];
  }
}
