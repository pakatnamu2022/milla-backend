<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateVehicleVNRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'vim' => [
        'nullable',
        'string',
        'max:17',
        'unique:vehicle_vn,vim'
      ],
      'order_number' => [
        'nullable',
        'string',
        'max:20',
        'unique:vehicle_vn,order_number'
      ],
      'year' => [
        'nullable',
        'integer',
        'min:1900',
        'max:2100'
      ],
      'engine_number' => [
        'nullable',
        'string',
        'max:30',
        'unique:vehicle_vn,engine_number'
      ],
      'ap_models_vn_id' => [
        'nullable',
        'integer',
        'exists:ap_models_vn,id'
      ],
      'vehicle_color_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id'
      ],
      'supplier_order_type_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id'
      ],
      'engine_type_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id'
      ],
      'sede_id' => [
        'nullable',
        'integer',
        'exists:ap_sedes,id'
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'vim.required' => 'El campo VIN es obligatorio.',
      'vim.string' => 'El campo VIN debe ser una cadena de texto.',
      'vim.max' => 'El campo VIN no debe exceder los 17 caracteres.',
      'vim.unique' => 'El campo VIN ya existe en la base de datos.',

      'order_number.required' => 'El campo Número de Pedido es obligatorio.',
      'order_number.string' => 'El campo Número de Pedido debe ser una cadena de texto.',
      'order_number.max' => 'El campo Número de Pedido no debe exceder los 20 caracteres.',
      'order_number.unique' => 'El campo Número de Pedido ya existe en la base de datos.',

      'year.required' => 'El campo Año es obligatorio.',
      'year.integer' => 'El campo Año debe ser un número entero.',
      'year.min' => 'El campo Año no debe ser menor a 1900.',
      'year.max' => 'El campo Año no debe ser mayor a 2100.',

      'engine_number.required' => 'El campo Número de Motor es obligatorio.',
      'engine_number.string' => 'El campo Número de Motor debe ser una cadena de texto.',
      'engine_number.max' => 'El campo Número de Motor no debe exceder los 30 caracteres.',
      'engine_number.unique' => 'El campo Número de Motor ya existe en la base de datos.',

      'ap_models_vn_id.required' => 'El campo Modelo VN es obligatorio.',
      'ap_models_vn_id.integer' => 'El campo Modelo VN debe ser un número entero.',
      'ap_models_vn_id.exists' => 'El Modelo VN seleccionado no existe en la base de datos.',

      'vehicle_color_id.required' => 'El campo Color del Vehículo es obligatorio.',
      'vehicle_color_id.integer' => 'El campo Color del Vehículo debe ser un número entero.',
      'vehicle_color_id.exists' => 'El Color del Vehículo seleccionado no existe en la base de datos.',

      'supplier_order_type_id.required' => 'El campo Tipo de Orden de Proveedor es obligatorio.',
      'supplier_order_type_id.integer' => 'El campo Tipo de Orden de Proveedor debe ser un número entero.',
      'supplier_order_type_id.exists' => 'El Tipo de Orden de Proveedor seleccionado no existe en la base de datos.',

      'engine_type_id.required' => 'El campo Tipo de Motor es obligatorio.',
      'engine_type_id.integer' => 'El campo Tipo de Motor debe ser un número entero.',
      'engine_type_id.exists' => 'El Tipo de Motor seleccionado no existe en la base de datos.',

      'sede_id.required' => 'El campo Sede es obligatorio.',
      'sede_id.integer' => 'El campo Sede debe ser un número entero.',
      'sede_id.exists' => 'La Sede seleccionada no existe en la base de datos.',
    ];
  }
}
