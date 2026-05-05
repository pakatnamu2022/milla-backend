<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateVehiclesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'plate' => 'sometimes|nullable|string|max:10',
      'vin' => [
        'sometimes',
        'required',
        'string',
        'max:20',
        'min:17',
        Rule::unique('ap_vehicles', 'vin')
          ->ignore($this->route('vehicle'))
          ->whereNull('deleted_at')
      ],
      'year' => 'sometimes|required|integer|min:1900|max:' . ((int)date('Y') + 2),
      'year_delivery' => 'sometimes|integer|min:1900|max:' . ((int)date('Y') + 3),
      'engine_number' => [
        'sometimes',
        'required',
        'string',
        'max:50',
        Rule::unique('ap_vehicles', 'engine_number')
          ->ignore($this->route('vehicle'))
          ->whereNull('deleted_at')
      ],
      'ap_models_vn_id' => 'sometimes|required|integer|exists:ap_models_vn,id',
      'vehicle_color_id' => 'sometimes|required|integer|exists:ap_masters,id',
      'supplier_order_type_id' => 'sometimes|nullable|integer|exists:ap_masters,id',
      'engine_type_id' => 'sometimes|required|integer|exists:ap_masters,id',
      'ap_vehicle_status_id' => 'sometimes|integer|exists:ap_vehicle_status,id',
      'sede_id' => 'sometimes|required|integer|exists:config_sede,id',
      'warehouse_physical_id' => 'sometimes|nullable|integer|exists:warehouse,id',
      'customer_id' => 'sometimes|nullable|integer|exists:business_partners,id',
      'is_heavy' => 'sometimes|required|boolean',
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array
   */
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
