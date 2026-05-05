<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreVehiclesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'plate' => 'sometimes|nullable|string|max:10|unique:ap_vehicles,plate',
      'vin' => 'required|string|max:20|min:17|unique:ap_vehicles,vin',
      'year' => 'required|integer|min:1900|max:' . ((int)date('Y') + 2),
      'year_delivery' => 'nullable|integer|min:1900|max:' . ((int)date('Y') + 3),
      'engine_number' => 'required|string|max:50|unique:ap_vehicles,engine_number',
      'ap_models_vn_id' => 'required|integer|exists:ap_models_vn,id',
      'vehicle_color_id' => 'required|integer|exists:ap_masters,id',
      'supplier_order_type_id' => 'sometimes|nullable|integer|exists:ap_masters,id',
      'engine_type_id' => 'required|integer|exists:ap_masters,id',
      'ap_vehicle_status_id' => 'sometimes|integer|exists:ap_vehicle_status,id',
      'sede_id' => 'required|integer|exists:config_sede,id',
      'warehouse_physical_id' => 'sometimes|nullable|integer|exists:warehouse,id',
      'type_operation_id' => 'sometimes|nullable|integer|exists:ap_masters,id',
      'customer_id' => 'sometimes|nullable|integer|exists:business_partners,id',
      'is_heavy' => 'sometimes|boolean',
    ];
  }

  public function attributes()
  {
    return [
      'plate' => 'placa',
      'vin' => 'vin',
      'year' => 'año',
      'year_delivery' => 'año delivery',
      'engine_number' => 'número de motor',
      'ap_models_vn_id' => 'modelo',
      'vehicle_color_id' => 'color',
      'supplier_order_type_id' => 'tipo de orden',
      'ap_vehicle_status_id' => 'estado del vehiculo',
      'sede_id' => 'sede',
      'warehouse_physical_id' => 'placa',
      'type_operation_id' => 'tipo de operacion',
      'customer_id' => 'cliente',
      'is_heavy' => 'es pesado',
    ];
  }
}
