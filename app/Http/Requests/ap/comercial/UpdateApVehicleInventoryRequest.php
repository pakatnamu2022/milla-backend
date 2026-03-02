<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateApVehicleInventoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_vehicle_id' => 'sometimes|nullable|integer|exists:ap_vehicles,id',
      'inventory_warehouse_id' => 'sometimes|nullable|integer|exists:warehouse,id',
      'vin' => 'sometimes|string|max:17',
      'vehicle_color_id' => 'sometimes|nullable|integer|exists:ap_masters,id',
      'brand_id' => 'sometimes|nullable|integer|exists:ap_vehicle_brand,id',
      'model_id' => 'sometimes|nullable|integer|exists:ap_models_vn,id',
      'year' => 'sometimes|nullable|integer|min:1900|max:' . ((int) date('Y') + 2),
      'fuel_type_id' => 'sometimes|nullable|integer|exists:ap_fuel_type,id',
      'adjudication_date' => 'sometimes|nullable|date',
      'days' => 'sometimes|nullable|integer|min:0',
      'limit_date' => 'sometimes|nullable|date',
      'reception_date' => 'sometimes|nullable|date',
      'is_location_confirmed' => 'sometimes|boolean',
      'is_evaluated' => 'sometimes|boolean',
      'status' => 'sometimes|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'vin.max' => 'El VIN no puede superar 17 caracteres',
      'ap_vehicle_id.exists' => 'El vehículo seleccionado no existe',
      'inventory_warehouse_id.exists' => 'El almacén seleccionado no existe',
      'brand_id.exists' => 'La marca seleccionada no existe',
      'model_id.exists' => 'El modelo seleccionado no existe',
      'fuel_type_id.exists' => 'El tipo de combustible seleccionado no existe',
      'year.min' => 'El año debe ser mayor a 1900',
    ];
  }
}
