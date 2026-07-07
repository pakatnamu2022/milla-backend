<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;

class ExportVehiclesRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'format'                       => 'nullable|in:excel,pdf',
      'title'                        => 'nullable|string|max:255',
      'columns'                      => 'nullable|array',
      'columns.*'                    => 'string',

      // Filtros
      'search'                       => 'nullable|string|max:255',
      'ap_models_vn_id'              => 'nullable|integer|exists:ap_models_vn,id',
      'model.class_id'               => 'nullable|integer',
      'warehouse_id'                 => 'nullable|integer|exists:ap_warehouse,id',
      'warehouse.sede_id'            => 'nullable|integer',
      'ap_vehicle_status_id'         => 'nullable|array',
      'ap_vehicle_status_id.*'       => 'integer|in:' . implode(',', ApVehicleStatus::ALL_STATUS),
      'vehicle_color_id'             => 'nullable|integer|exists:ap_masters,id',
      'engine_type_id'               => 'nullable|integer|exists:ap_masters,id',
      'warehouse_physical_id'        => 'nullable|integer|exists:ap_warehouse,id',
      'warehousePhysical.sede_id'    => 'nullable|integer',
      'year'                         => 'nullable|integer|min:1900|max:2100',
      'has_purchase_request_quote'   => 'nullable|boolean',
      'customer_id'                  => 'nullable|integer|exists:business_partners,id',
      'type_operation_id'            => 'nullable|integer|exists:ap_masters,id',
      'is_paid'                      => 'nullable|boolean',
      'is_received'                  => 'nullable|boolean',
      'has_delivery_guide'           => 'nullable|boolean',
      'has_vehicle_delivery'         => 'nullable|boolean',
    ];
  }

  public function attributes(): array
  {
    return [
      'format'                    => 'formato',
      'title'                     => 'título',
      'columns'                   => 'columnas',
      'search'                    => 'búsqueda',
      'ap_models_vn_id'           => 'modelo',
      'warehouse_id'              => 'almacén',
      'ap_vehicle_status_id'      => 'estado de vehículo',
      'vehicle_color_id'          => 'color',
      'engine_type_id'            => 'tipo de motor',
      'warehouse_physical_id'     => 'almacén físico',
      'year'                      => 'año',
      'customer_id'               => 'cliente',
      'type_operation_id'         => 'tipo de operación',
      'is_paid'                   => 'pagado',
      'is_received'               => 'recibido',
      'has_delivery_guide'        => 'guía de entrega',
      'has_vehicle_delivery'      => 'entrega de vehículo',
    ];
  }
}
