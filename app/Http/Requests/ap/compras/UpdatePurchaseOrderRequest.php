<?php

namespace App\Http\Requests\ap\compras;

use App\Http\Requests\StoreRequest;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Información de la Factura (Cabecera) - Todos opcionales en update
      'invoice_series' => ['sometimes', 'string', 'max:10'],
      'invoice_number' => ['sometimes', 'string', 'max:20'],
      'emission_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
      'due_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:emission_date'],

      // Valores de la Factura (vienen de la factura física, NO se calculan)
      'subtotal' => ['sometimes', 'numeric', 'min:0'],
      'igv' => ['sometimes', 'numeric', 'min:0'],
      'total' => ['sometimes', 'numeric', 'min:0'],
      'payment_term' => ['sometimes', 'nullable', 'string', 'max:100'],
      'discount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
      'isc' => ['sometimes', 'nullable', 'numeric', 'min:0'],

      // Relaciones
      'supplier_id' => ['sometimes', 'integer', Rule::exists('business_partners', 'id')->where('status_ap', 1)->whereNull('deleted_at')],
      'currency_id' => ['sometimes', 'integer', Rule::exists('type_currency', 'id')->where('status', 1)->whereNull('deleted_at')],
      'exchange_rate_id' => ['sometimes', 'nullable', 'integer', Rule::exists('ap_exchange_rate', 'id')],
      'warehouse_id' => ['sometimes', 'integer', Rule::exists('warehouse', 'id')->where('type', Warehouse::REAL)->where('status', 1)->whereNull('deleted_at')],

      // Movimiento de Vehículo (opcional)
      'vehicle_movement_id' => ['sometimes', 'nullable', 'integer', Rule::exists('ap_vehicle_movement', 'id')->whereNull('deleted_at')],

      // Tipo de Operación (opcional)
      'type_operation_id' => ['sometimes', 'nullable', 'integer', Rule::exists('ap_commercial_masters', 'id')->where('status', 1)->whereNull('deleted_at')],

      'payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],

      'notes' => ['nullable', 'string', 'max:1000'],

      // Items de la Orden de Compra (opcional, si se envía se reemplazan todos)
      'items' => ['sometimes', 'array', 'min:1'],
      'items.*.unit_measurement_id' => ['sometimes', 'integer', Rule::exists('unit_measurement', 'id')->where('status', 1)->whereNull('deleted_at')],
      'items.*.description' => ['sometimes', 'string', 'max:255'],
      'items.*.unit_price' => ['required', 'numeric', 'min:0'],
      'items.*.quantity' => ['required', 'integer', 'min:1'],
      'items.*.is_vehicle' => ['sometimes', 'boolean'],
      'items.*.product_id' => ['required_if:items.*.is_vehicle,false', 'nullable', 'integer', Rule::exists('products', 'id')->where('status', 'ACTIVE')->whereNull('deleted_at')],

      // Campos de estado
      'migration_status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'failed', 'updated_with_nc'])],
    ];
  }

  public function attributes()
  {
    return [
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
      'exchange_rate_id' => 'Tipo de Cambio',
      'warehouse_id' => 'Almacén',
      'vehicle_movement_id' => 'Movimiento de Vehículo',

      // Items
      'items' => 'Items de la Orden',
      'items.*.unit_measurement_id' => 'Unidad de Medida',
      'items.*.description' => 'Descripción del Item',
      'items.*.unit_price' => 'Precio Unitario',
      'items.*.quantity' => 'Cantidad',
      'items.*.is_vehicle' => 'Es Vehículo',

      // Estado
      'migration_status' => 'Estado de Migración',
    ];
  }

  public function messages()
  {
    return [
      'id.required' => 'El ID de la orden de compra es requerido',
      'id.exists' => 'La orden de compra no existe',
      'items.min' => 'Debe agregar al menos un item a la orden de compra',
      'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión',
      'migration_status.in' => 'El estado de migración debe ser uno de: pending, in_progress, completed, failed, updated_with_nc',
    ];
  }
}
