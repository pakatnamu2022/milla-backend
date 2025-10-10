<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreVehiclePurchaseOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Vehicle
      'vin' => [
        'required',
        'string',
        'max:17',
        Rule::unique('ap_vehicle_purchase_order', 'vin')
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
        Rule::unique('ap_vehicle_purchase_order', 'engine_number')
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

      // Invoice
      'invoice_series' => [
        'nullable',
        'string',
        'max:10'
      ],
      'invoice_number' => [
        'nullable',
        'string',
        'max:20'
      ],
      'emission_date' => [
        'nullable',
        'date'
      ],
      'unit_price' => [
        'nullable',
        'numeric',
        'min:0'
      ],
      'discount' => [
        'nullable',
        'numeric',
        'min:0'
      ],
      'subtotal' => [
        'nullable',
        'numeric',
        'min:0'
      ],
      'igv' => [
        'nullable',
        'numeric',
        'min:0'
      ],
      'total' => [
        'nullable',
        'numeric',
        'min:0'
      ],
      'supplier_id' => [
        'nullable',
        'integer'
      ],
      'currency_id' => [
        'nullable',
        'integer'
      ],
      'exchange_rate_id' => [
        'nullable',
        'integer'
      ],

      // Guide
      'number' => [
        'nullable',
        'string',
        'max:20'
      ],
      'number_guide' => [
        'nullable',
        'string',
        'max:20'
      ],
      'warehouse_id' => [
        'nullable',
        'integer'
      ],
      'warehouse_physical_id' => [
        'nullable',
        'integer'
      ],
    ];
  }

  public function messages(): array
  {
    return [
      // Vehicle
      'vin.required' => 'El campo VIN es obligatorio.',
      'vin.string' => 'El campo VIN debe ser una cadena de texto.',
      'vin.max' => 'El campo VIN no debe exceder los 17 caracteres.',
      'vin.unique' => 'El campo VIN ya existe.',

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

      // Invoice
      'invoice_series.string' => 'El campo Serie de Factura debe ser una cadena de texto.',
      'invoice_series.max' => 'El campo Serie de Factura no debe exceder los 10 caracteres.',

      'invoice_number.string' => 'El campo Número de Factura debe ser una cadena de texto.',
      'invoice_number.max' => 'El campo Número de Factura no debe exceder los 20 caracteres.',

      'emission_date.date' => 'El campo Fecha de Emisión debe ser una fecha válida.',

      'unit_price.numeric' => 'El campo Precio Unitario debe ser un número.',
      'unit_price.min' => 'El campo Precio Unitario no puede ser negativo.',

      'discount.numeric' => 'El campo Descuento debe ser un número.',
      'discount.min' => 'El campo Descuento no puede ser negativo.',

      'subtotal.numeric' => 'El campo Subtotal debe ser un número.',
      'subtotal.min' => 'El campo Subtotal no puede ser negativo.',

      'igv.numeric' => 'El campo IGV debe ser un número.',
      'igv.min' => 'El campo IGV no puede ser negativo.',

      'total.numeric' => 'El campo Total debe ser un número.',
      'total.min' => 'El campo Total no puede ser negativo.',

      // Guide
      'number.string' => 'El campo Número debe ser una cadena de texto.',
      'number.max' => 'El campo Número no debe exceder los 20 caracteres.',

      'number_guide.string' => 'El campo Número de Guía debe ser una cadena de texto.',
      'number_guide.max' => 'El campo Número de Guía no debe exceder los 20 caracteres.',
    ];
  }
}
