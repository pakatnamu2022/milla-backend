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
    ];
  }

  public function messages(): array
  {
    return [
      'plate.unique' => 'La placa ya está registrada en el sistema.',
      'vin.unique' => 'El VIN ya está registrado en el sistema.',
      'engine_number.unique' => 'El número de motor ya está registrado en el sistema.',
    ];
  }
}
