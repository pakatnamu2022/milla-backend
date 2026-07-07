<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreVehiclesReplacementRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'plate' => 'sometimes|nullable|string|max:10|unique:ap_vehicles,plate',
      'vin' => 'required|string|max:20|min:17|unique:ap_vehicles,vin',
      'engine_number' => 'required|string|max:50|unique:ap_vehicles,engine_number',
      'ap_models_vn_id' => 'required|integer|exists:ap_models_vn,id',
      'sede_id' => 'required|integer|exists:config_sede,id',
    ];
  }

  public function messages(): array
  {
    return [
      'plate.unique' => 'La placa ya está registrada en el sistema.',
      'vin.unique' => 'El VIN ya está registrado en el sistema.',
      'engine_number.required' => 'El número de motor es obligatorio.',
      'engine_number.unique' => 'El número de motor ya está registrado en el sistema.',
      'ap_models_vn_id.required' => 'El modelo es obligatorio.',
      'ap_models_vn_id.exists' => 'El modelo seleccionado no existe.',
      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
    ];
  }
}
