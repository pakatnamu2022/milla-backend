<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class ExportVehiclesDeliveryRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'format' => 'nullable|in:excel,pdf',
      'title' => 'nullable|string|max:255',

      'search' => 'nullable|string|max:255',
      'ap_models_vn_id' => 'nullable|integer|exists:ap_models_vn,id',
      'warehouse_id' => 'nullable|integer|exists:ap_warehouse,id',
      'type_operation_id' => 'nullable|integer|exists:ap_masters,id',
      'has_vehicle_delivery' => 'nullable|boolean',
    ];
  }

  public function attributes(): array
  {
    return [
      'format' => 'formato',
      'title' => 'título',
      'search' => 'búsqueda',
      'ap_models_vn_id' => 'modelo',
      'warehouse_id' => 'almacén',
      'type_operation_id' => 'tipo de operación',
      'has_vehicle_delivery' => 'tiene entrega',
    ];
  }
}
