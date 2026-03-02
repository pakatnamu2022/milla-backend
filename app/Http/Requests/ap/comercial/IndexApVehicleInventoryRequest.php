<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexApVehicleInventoryRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'ap_vehicle_id' => 'nullable|integer|exists:ap_vehicles,id',
      'inventory_warehouse_id' => 'nullable|integer|exists:warehouse,id',
      'brand_id' => 'nullable|integer|exists:ap_vehicle_brand,id',
      'model_id' => 'nullable|integer|exists:ap_models_vn,id',
      'year' => 'nullable|integer',
      'fuel_type_id' => 'nullable|integer|exists:ap_fuel_type,id',
      'is_location_confirmed' => 'nullable|boolean',
      'is_evaluated' => 'nullable|boolean',
      'status' => 'nullable|boolean',
      'inventoryWarehouse.sede_id' => 'nullable|integer',
    ];
  }
}
