<?php

namespace App\Http\Requests\ap\compras;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends StoreRequest
{

  public function prepareForValidation(): void
  {
    if (!$this->has('type_operation_id')) {
      $this->merge([
        'type_operation_id' => ApCommercialMasters::TIPO_OPERACION_COMERCIAL,
      ]);
    }
  }

  public function rules(): array
  {
    $hasVehicle = false;
    if ($this->has('items') && is_array($this->items)) {
      foreach ($this->items as $item) {
        if (isset($item['is_vehicle']) && $item['is_vehicle'] === true) {
          $hasVehicle = true;
          break;
        }
      }
    }

    $vehicleRules = $hasVehicle ? [
      'vin' => ['required', 'string', 'max:17', Rule::unique('ap_vehicles', 'vin')->whereNull('deleted_at')->where('status', 1)],
      'year' => ['required', 'integer', 'min:1900', 'max:2100'],
      'engine_number' => ['required', 'string', 'max:30', Rule::unique('ap_vehicles', 'engine_number')->whereNull('deleted_at')->where('status', 1)],
      'ap_models_vn_id' => ['required', 'integer', Rule::exists('ap_models_vn', 'id')->where('status', 1)->whereNull('deleted_at')],
      'vehicle_color_id' => ['required', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'COLOR_VEHICULO')->where('status', 1)->whereNull('deleted_at')],
      'engine_type_id' => ['required', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_MOTOR')->where('status', 1)->whereNull('deleted_at')],
    ] : [
      'vin' => ['nullable', 'string', 'max:17'],
      'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
      'engine_number' => ['nullable', 'string', 'max:30'],
      'ap_models_vn_id' => ['nullable', 'integer', Rule::exists('ap_models_vn', 'id')->where('status', 1)->whereNull('deleted_at')],
      'vehicle_color_id' => ['nullable', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'COLOR_VEHICULO')->where('status', 1)->whereNull('deleted_at')],
      'engine_type_id' => ['nullable', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_MOTOR')->where('status', 1)->whereNull('deleted_at')],
    ];

    return array_merge($vehicleRules, [
      // Información de la Factura (Cabecera)
      'invoice_series' => ['required', 'string', 'max:10'],
      'invoice_number' => ['required', 'string', 'max:20'],
      'emission_date' => ['required', 'date', 'date_format:Y-m-d'],
      'due_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:emission_date'],

      // Valores de la Factura (vienen de la factura física, NO se calculan)
      'subtotal' => ['required', 'numeric', 'min:0'],
      'igv' => ['required', 'numeric', 'min:0'],
      'total' => ['required', 'numeric', 'min:0'],
      'payment_term' => ['nullable', 'string', 'max:100'],
      'discount' => ['nullable', 'numeric', 'min:0'],
      'isc' => ['nullable', 'numeric', 'min:0'],

      // Relaciones
      'sede_id' => ['required', 'integer', Rule::exists('config_sede', 'id')->where('status', 1)->whereNull('deleted_at')],
      'supplier_order_type_id' => ['sometimes', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_PEDIDO_PROVEEDOR')->where('status', 1)->whereNull('deleted_at')],
      'supplier_id' => ['required', 'integer', Rule::exists('business_partners', 'id')->where('status_ap', 1)->whereNull('deleted_at')],
      'currency_id' => ['required', 'integer', Rule::exists('type_currency', 'id')->where('status', 1)->whereNull('deleted_at')],
      'warehouse_id' => ['required', 'integer', Rule::exists('warehouse', 'id')->where('type', Warehouse::REAL)->where('status', 1)->whereNull('deleted_at')],

      // Movimiento de Vehículo (opcional, solo si la OC está relacionada a un movimiento)
      'vehicle_movement_id' => ['nullable', 'integer', Rule::exists('ap_vehicle_movement', 'id')->whereNull('deleted_at')],

      // Tipo de Operación (opcional)
      'type_operation_id' => ['required', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('type', 'TIPO_OPERACION')->where('status', 1)->whereNull('deleted_at')],

      'payment_terms' => ['nullable', 'string', 'max:100'],

      'notes' => ['nullable', 'string', 'max:1000'],

      // Items de la Orden de Compra
      'items' => ['required', 'array', 'min:1'],
      'items.*.unit_measurement_id' => ['nullable', 'integer', Rule::exists('unit_measurement', 'id')->where('status', 1)->whereNull('deleted_at')],
      'items.*.description' => ['nullable', 'string', 'max:255'],
      'items.*.unit_price' => ['required', 'numeric', 'min:0'],
      'items.*.quantity' => ['required', 'integer', 'min:1'],
      'items.*.is_vehicle' => ['nullable', 'boolean'],
      'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('status', 'ACTIVE')->whereNull('deleted_at')],
    ]);
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

      // Factura
      'invoice_series' => 'Serie de la Factura',
      'invoice_number' => 'Número de la Factura',
      'emission_date' => 'Fecha de Emisión',
      'due_date' => 'Fecha de Vencimiento',

      // Valores
      'subtotal' => 'Subtotal',
      'igv' => 'IGV',
      'total' => 'Total',
      'discount' => 'Descuento',
      'isc' => 'ISC',

      // Relaciones
      'supplier_id' => 'Proveedor',
      'currency_id' => 'Moneda',
      'warehouse_id' => 'Almacén',
      'vehicle_movement_id' => 'Movimiento de Vehículo',

      // Items
      'items' => 'Items de la Orden',
      'items.*.unit_measurement_id' => 'Unidad de Medida',
      'items.*.description' => 'Descripción del Item',
      'items.*.unit_price' => 'Precio Unitario',
      'items.*.quantity' => 'Cantidad',
      'items.*.is_vehicle' => 'Es Vehículo',
    ];
  }

  public function messages()
  {
    return [
      'items.required' => 'Debe agregar al menos un item a la orden de compra',
      'items.min' => 'Debe agregar al menos un item a la orden de compra',
      'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión',
      'subtotal.required' => 'El subtotal es requerido (debe venir de la factura)',
      'igv.required' => 'El IGV es requerido (debe venir de la factura)',
      'total.required' => 'El total es requerido (debe venir de la factura)',
    ];
  }
}
