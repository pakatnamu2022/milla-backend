<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class ExportPurchaseRequestQuoteRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'format'    => 'nullable|in:excel,pdf',
      'title'     => 'nullable|string|max:255',
      'columns'   => 'nullable|array',
      'columns.*' => 'string',

      // Filtros generales
      'search'              => 'nullable|string|max:255',
      'sede_id'             => 'nullable|integer|exists:sedes,id',
      'type_document'       => 'nullable|string|max:50',
      'type_vehicle'        => 'nullable|string|max:50',
      'status'              => 'nullable|boolean|in:0,1',
      'is_approved'         => 'nullable|boolean|in:0,1',
      'is_invoiced'         => 'nullable|boolean|in:0,1',
      'has_vehicle'         => 'nullable|boolean|in:0,1',
      'is_paid'             => 'nullable|boolean|in:0,1',

      // Filtros de relaciones
      'opportunity_id'        => 'nullable|integer|exists:opportunities,id',
      'holder_id'             => 'nullable|integer|exists:business_partners,id',
      'ap_models_vn_id'       => 'nullable|integer|exists:ap_models_vn,id',
      'apModelsVn.family.brand_id' => 'nullable|integer|exists:ap_brands,id',
      'vehicle_color_id'      => 'nullable|integer|exists:ap_masters,id',
      'ap_vehicle_id'         => 'nullable|integer|exists:ap_vehicles,id',
      'doc_type_currency_id'  => 'nullable|integer|exists:type_currencies,id',

      // Filtro de rango de fechas
      'created_at'      => 'nullable|array|size:2',
      'created_at.0'    => 'nullable|date',
      'created_at.1'    => 'nullable|date|after_or_equal:created_at.0',
    ];
  }

  public function attributes(): array
  {
    return [
      'format'                     => 'formato',
      'title'                      => 'título',
      'columns'                    => 'columnas',
      'search'                     => 'búsqueda',
      'sede_id'                    => 'sede',
      'type_document'              => 'tipo de documento',
      'type_vehicle'               => 'tipo de vehículo',
      'status'                     => 'estado',
      'is_approved'                => 'aprobado',
      'is_invoiced'                => 'facturado',
      'has_vehicle'                => 'tiene vehículo',
      'is_paid'                    => 'pagado',
      'opportunity_id'             => 'oportunidad',
      'holder_id'                  => 'titular',
      'ap_models_vn_id'            => 'modelo de vehículo',
      'apModelsVn.family.brand_id' => 'marca',
      'vehicle_color_id'           => 'color',
      'ap_vehicle_id'              => 'vehículo',
      'doc_type_currency_id'       => 'moneda documento',
      'created_at'                 => 'rango de fechas',
      'created_at.0'               => 'fecha desde',
      'created_at.1'               => 'fecha hasta',
    ];
  }
}
