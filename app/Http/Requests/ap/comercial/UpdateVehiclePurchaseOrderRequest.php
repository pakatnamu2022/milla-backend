<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Validation\Rule;

class UpdateVehiclePurchaseOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Vehicle
      'vin' => [
        'nullable',
        'string',
        'max:17',
        Rule::unique('ap_vehicle_purchase_order', 'vin')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehiclePurchaseOrder')),
      ],
      'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
      'engine_number' => [
        'nullable',
        'string',
        'max:30',
        Rule::unique('ap_vehicle_purchase_order', 'engine_number')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehiclePurchaseOrder')),
      ],
      'ap_models_vn_id' => ['nullable', 'integer', Rule::exists('ap_models_vn', 'id')->where('status', 1)->whereNull('deleted_at')],
      'vehicle_color_id' => ['nullable', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'COLOR_VEHICULO')->where('status', 1)->whereNull('deleted_at')],
      'supplier_order_type_id' => ['nullable', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_PEDIDO_PROVEEDOR')->where('status', 1)->whereNull('deleted_at')],
      'engine_type_id' => ['nullable', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_MOTOR')->where('status', 1)->whereNull('deleted_at')],
      'sede_id' => ['nullable', 'integer', Rule::exists('config_sede', 'id')->where('status', 1)->whereNull('deleted_at')],

      // Invoice
      'invoice_series' => ['nullable', 'string', 'max:10'],
      'invoice_number' => ['nullable', 'string', 'max:20'],
      'emission_date' => ['nullable', 'date'],
      'unit_price' => ['nullable', 'numeric', 'min:0'],
      'discount' => ['nullable', 'numeric', 'min:0'],
      'supplier_id' => ['nullable', 'integer', Rule::exists('business_partners', 'id')->where('status_ap', 1)->whereNull('deleted_at')],
      'currency_id' => ['nullable', 'integer', Rule::exists('type_currency', 'id')->where('status', 1)->whereNull('deleted_at')],

      // Guide
      'number' => ['nullable', 'string', 'max:20'],
      'number_guide' => ['nullable', 'string', 'max:20'],
      'warehouse_id' => ['nullable', 'integer', Rule::exists('warehouse', 'id')->where('type', Warehouse::REAL)->where('status', 1)->whereNull('deleted_at')],
    ];
  }

  public function attributes()
  {
    return [
      // Vehicle
      'vin' => 'VIN',
      'year' => 'Año',
      'engine_number' => 'Número de Motor',
      'ap_models_vn_id' => 'Modelo VN',
      'vehicle_color_id' => 'Color del Vehículo',
      'supplier_order_type_id' => 'Tipo de Pedido de Proveedor',
      'engine_type_id' => 'Tipo de Motor',
      'sede_id' => 'Sede',

      // Invoice
      'invoice_series' => 'Serie de la Factura',
      'invoice_number' => 'Número de la Factura',
      'emission_date' => 'Fecha de Emisión',
      'unit_price' => 'Precio Unitario',
      'discount' => 'Descuento',
      'subtotal' => 'Subtotal',
      'igv' => 'IGV',
      'total' => 'Total',
      'supplier_id' => 'Proveedor',
      'currency_id' => 'Moneda',
      'exchange_rate_id' => 'Tipo de Cambio',

      // Guide
      'number' => 'Número',
      'number_guide' => 'Número de Guía',
      'warehouse_id' => 'Almacén',
      'warehouse_physical_id' => 'Almacén Físico',
    ];
  }
}
