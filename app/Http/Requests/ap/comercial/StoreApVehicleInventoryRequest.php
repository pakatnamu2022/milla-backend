<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreApVehicleInventoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_vehicle_id' => 'sometimes|nullable|integer|exists:ap_vehicles,id',
      'inventory_warehouse_id' => 'sometimes|nullable|integer|exists:warehouse,id',
      'vin' => 'required|string|max:20',
      'vehicle_color_id' => 'sometimes|nullable|integer|exists:ap_masters,id',
      'brand_id' => 'sometimes|nullable|integer|exists:ap_vehicle_brand,id',
      'model_id' => 'sometimes|nullable|integer|exists:ap_models_vn,id',
      'year' => 'sometimes|nullable|integer|min:1900|max:' . ((int)date('Y') + 2),
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

  public function attributes(): array
  {
    return [
      'ap_vehicle_id' => 'vehículo',
      'inventory_warehouse_id' => 'almacenamiento',
      'vin' => 'vin',
      'vehicle_color_id' => 'color',
      'brand_id' => 'marca',
      'model_id' => 'modelo',
      'year' => 'anio',
      'fuel_type_id' => 'combustible',
      'adjudication_date' => 'fecha',
      'days' => 'dias',
      'limit_date' => 'limite',
      'reception_date' => 'fecha',
      'is_location_confirmed' => 'estado',
      'is_evaluated' => 'estado',
      'status' => 'estado',
    ];
  }
}
