<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Validation\Rule;

class IndexVehiclesRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      // Búsqueda general
      'search'                         => 'nullable|string|max:255',

      // Modelo
      'ap_models_vn_id'                => 'nullable|integer|exists:ap_models_vn,id',
      'model.class_id'                 => 'nullable|integer',

      // Estado
      'ap_vehicle_status_id'           => 'nullable|array',
      'ap_vehicle_status_id.*'         => ['integer', Rule::exists('ap_vehicle_status', 'id')],

      // Almacenes
      'warehouse_id'                   => 'nullable|integer|exists:ap_warehouse,id',
      'warehouse_physical_id'          => 'nullable|integer|exists:ap_warehouse,id',
      'warehouse.sede_id'              => 'nullable|integer',
      'warehouse.is_received'          => 'nullable|boolean',
      'warehouse.article_class_id'     => 'nullable|integer',
      'warehousePhysical.sede_id'      => 'nullable|integer',
      'warehousePhysical.is_received'  => 'nullable|boolean',
      'warehousePhysical.article_class_id' => 'nullable|integer',

      // Características del vehículo
      'vehicle_color_id'               => 'nullable|integer|exists:ap_masters,id',
      'engine_type_id'                 => 'nullable|integer|exists:ap_masters,id',
      'year'                           => 'nullable|integer|min:1900|max:2100',
      'type_operation_id'              => 'nullable|integer|exists:ap_masters,id',

      // Cliente
      'customer_id'                    => 'nullable|integer|exists:business_partners,id',

      // Accesores booleanos
      'has_purchase_request_quote'     => 'nullable|boolean',
      'is_paid'                        => 'nullable|boolean',
      'is_received'                    => 'nullable|boolean',
      'has_delivery_guide'             => 'nullable|boolean',
      'has_vehicle_delivery'           => 'nullable|boolean',
    ];
  }

  public function attributes(): array
  {
    return [
      'search'                         => 'búsqueda',
      'ap_models_vn_id'                => 'modelo',
      'model.class_id'                 => 'clase de modelo',
      'ap_vehicle_status_id'           => 'estado de vehículo',
      'warehouse_id'                   => 'almacén',
      'warehouse_physical_id'          => 'almacén físico',
      'warehouse.sede_id'              => 'sede del almacén',
      'warehouse.is_received'          => 'recibido en almacén',
      'warehouse.article_class_id'     => 'clase de artículo del almacén',
      'warehousePhysical.sede_id'      => 'sede del almacén físico',
      'warehousePhysical.is_received'  => 'recibido en almacén físico',
      'warehousePhysical.article_class_id' => 'clase de artículo del almacén físico',
      'vehicle_color_id'               => 'color',
      'engine_type_id'                 => 'tipo de motor',
      'year'                           => 'año',
      'type_operation_id'              => 'tipo de operación',
      'customer_id'                    => 'cliente',
      'has_purchase_request_quote'     => 'cotización asociada',
      'is_paid'                        => 'pagado',
      'is_received'                    => 'recibido',
      'has_delivery_guide'             => 'guía de entrega',
      'has_vehicle_delivery'           => 'entrega de vehículo',
    ];
  }
}
