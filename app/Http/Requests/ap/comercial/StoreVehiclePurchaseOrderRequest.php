<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Validation\Rule;

class StoreVehiclePurchaseOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Vehicle
      'vin' => ['required', 'string', 'max:17', Rule::unique('ap_vehicle_purchase_order', 'vin')->whereNull('deleted_at'),],
      'year' => ['required', 'integer', 'min:1900', 'max:2100'],
      'engine_number' => ['required', 'string', 'max:30', Rule::unique('ap_vehicle_purchase_order', 'engine_number')->whereNull('deleted_at'),],
      'ap_models_vn_id' => ['required', 'integer', Rule::exists('ap_models', 'id')->where('status', 1)->whereNull('deleted_at')],
      'vehicle_color_id' => ['required', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'COLOR_VEHICULO')->where('status', 1)->whereNull('deleted_at')],
      'supplier_order_type_id' => ['required', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_PEDIDO_PROVEEDOR')->where('status', 1)->whereNull('deleted_at')],
      'engine_type_id' => ['required', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_MOTOR')->where('status', 1)->whereNull('deleted_at')],
      'sede_id' => ['required', 'integer', Rule::exists('config_sede', 'id')->where('status', 1)->whereNull('deleted_at')],

      // Invoice
      'invoice_series' => ['required', 'string', 'max:10'],
      'invoice_number' => ['required', 'string', 'max:20'],
      'emission_date' => ['required', 'date'],
      'unit_price' => ['required', 'numeric', 'min:0'],
      'discount' => ['required', 'numeric', 'min:0'],
//      'subtotal' => ['required', 'numeric', 'min:0'],
//      'igv' => ['required', 'numeric', 'min:0'],
//      'total' => ['required', 'numeric', 'min:0'],
      'supplier_id' => ['required', 'integer', Rule::exists('business_partners', 'id')->where('status_ap', 1)->whereNull('deleted_at')],
      'currency_id' => ['required', 'integer', Rule::exists('type_currency', 'id')->where('status', 1)->whereNull('deleted_at')],
//      'exchange_rate_id' => ['required', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_MONEDA')->whereNull('deleted_at')],

      // Guide
      'number' => ['required', 'string', 'max:20'],
      'number_guide' => ['required', 'string', 'max:20'],
      'warehouse_id' => ['required', 'integer', Rule::exists('warehouse', 'id')->where('type', Warehouse::REAL)->where('status', 1)->whereNull('deleted_at')],
      'warehouse_physical_id' => ['required', 'integer', Rule::exists('warehouse', 'id')->where('type', Warehouse::PHYSICAL)->where('status', 1)->whereNull('deleted_at')]
    ];
  }

  public function attributes()
  {
    return [
      // Vehicle
      'vin' => 'VIN',
      'year' => 'Año',
      'engine_number' => 'Número de motor',
      'ap_models_vn_id' => 'Modelo VN',
      'vehicle_color_id' => 'Color del vehículo',
      'supplier_order_type_id' => 'Tipo de orden de proveedor',
      'engine_type_id' => 'Tipo de motor',
      'sede_id' => 'Sede',

      // Invoice
      'invoice_series' => 'Serie de la factura',
      'invoice_number' => 'Número de la factura',
      'emission_date' => 'Fecha de emisión',
      'unit_price' => 'Precio unitario',
      'discount' => 'Descuento',
      'subtotal' => 'Subtotal',
      'igv' => 'IGV',
      'total' => 'Total',
      'supplier_id' => 'Proveedor',
      'currency_id' => 'Moneda',
      'exchange_rate_id' => 'Tipo de cambio',

      // Guide
      'number' => 'Número',
      'number_guide' => 'Número de guía',
      'warehouse_id' => 'Almacén',
      'warehouse_physical_id' => 'Almacén físico',
    ];
  }
}
