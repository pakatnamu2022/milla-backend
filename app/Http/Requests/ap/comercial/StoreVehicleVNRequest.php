<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreVehicleVNRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'vin' => [
        'required',
        'string',
        'max:17',
        Rule::unique('vehicle_vn', 'vin')
          ->whereNull('deleted_at'),
      ],
      'order_number' => [
        'required',
        'string',
        'max:20',
        Rule::unique('vehicle_vn', 'order_number')
          ->whereNull('deleted_at'),
      ],
      'year' => [
        'required',
        'integer',
        'min:1900',
        'max:2100'
      ],
      'engine_number' => [
        'required',
        'string',
        'max:30',
        Rule::unique('vehicle_vn', 'engine_number')
          ->whereNull('deleted_at'),
      ],
      'ap_models_vn_id' => [
        'required',
        'integer',
        'exists:ap_models_vn,id'
      ],
      'vehicle_color_id' => [
        'required',
        'integer',
        'exists:ap_commercial_masters,id'
      ],
      'supplier_order_type_id' => [
        'required',
        'integer',
        'exists:ap_commercial_masters,id'
      ],
      'engine_type_id' => [
        'required',
        'integer',
        'exists:ap_commercial_masters,id'
      ],
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id'
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'vin.required' => 'El campo VIN es obligatorio.',
      'vin.string' => 'El campo VIN debe ser una cadena de texto.',
      'vin.max' => 'El campo VIN no debe exceder los 17 caracteres.',
      'vin.unique' => 'El campo VIN ya existe.',

      'order_number.required' => 'El campo Número de Pedido es obligatorio.',
      'order_number.string' => 'El campo Número de Pedido debe ser una cadena de texto.',
      'order_number.max' => 'El campo Número de Pedido no debe exceder los 20 caracteres.',
      'order_number.unique' => 'El campo Número de Pedido ya existe.',

      'year.required' => 'El campo Año es obligatorio.',
      'year.integer' => 'El campo Año debe ser un número entero.',
      'year.min' => 'El campo Año no puede ser menor a 1900.',
      'year.max' => 'El campo Año no puede ser mayor a 2100.',

      'engine_number.required' => 'El campo Número de Motor es obligatorio.',
      'engine_number.string' => 'El campo Número de Motor debe ser una cadena de texto.',
      'engine_number.max' => 'El campo Número de Motor no debe exceder los 30 caracteres.',
      'engine_number.unique' => 'El campo Número de Motor ya existe.',

      'ap_models_vn_id.required' => 'El campo Modelo VN es obligatorio.',
      'ap_models_vn_id.integer' => 'El campo Modelo VN debe ser un número entero.',
      'ap_models_vn_id.exists' => 'El modelo VN seleccionado no existe.',

      'vehicle_color_id.required' => 'El campo Color del Vehículo es obligatorio.',
      'vehicle_color_id.integer' => 'El campo Color del Vehículo debe ser un número entero.',
      'vehicle_color_id.exists' => 'El color del vehículo seleccionado no existe.',

      'supplier_order_type_id.required' => 'El campo Tipo de Orden de Proveedor es obligatorio.',
      'supplier_order_type_id.integer' => 'El campo Tipo de Orden de Proveedor debe ser un número entero.',
      'supplier_order_type_id.exists' => 'El tipo de orden de proveedor seleccionado no existe.',

      'engine_type_id.required' => 'El campo Tipo de Motor es obligatorio.',
      'engine_type_id.integer' => 'El campo Tipo de Motor debe ser un número entero.',
      'engine_type_id.exists' => 'El tipo de motor seleccionado no existe.',

      'sede_id.required' => 'El campo Sede es obligatorio.',
      'sede_id.integer' => 'El campo Sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
    ];
  }
}
