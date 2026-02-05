<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\IndexRequest;
use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Validation\Rule;

class IndexElectronicDocumentRequest extends IndexRequest
{
  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // Paginación
      'page' => 'nullable|integer|min:1',
      'per_page' => 'nullable|integer|min:1|max:100',

      // Ordenamiento
      'sort_by' => [
        'nullable',
        'string',
        Rule::in(['id', 'fecha_de_emision', 'numero', 'total', 'created_at', 'updated_at'])
      ],
      'sort_direction' => [
        'nullable',
        'string',
        Rule::in(['asc', 'desc'])
      ],

      // Filtros opcionales
      'ap_billing_document_type_id' => 'nullable|integer|exists:ap_billing_document_types,id',
      'serie' => 'nullable|string|max:4',
      'numero' => 'nullable|integer|min:1',
      'area_id' => [
        'nullable',
        'string',
        Rule::in(ApMasters::ALL_AREAS)
      ],
      'status' => [
        'nullable',
        'string',
        Rule::in(['draft', 'sent', 'accepted', 'rejected', 'cancelled'])
      ],
      'aceptada_por_sunat' => 'nullable|boolean',
      'anulado' => 'nullable|boolean',
      'fecha_de_emision_desde' => 'nullable|date',
      'fecha_de_emision_hasta' => 'nullable|date|after_or_equal:fecha_de_emision_desde',
      'cliente_numero_de_documento' => 'nullable|string|max:15',
      'cliente_denominacion' => 'nullable|string|max:100',

      // Filtros adicionales
      'ap_billing_currency_id' => 'nullable|integer|exists:ap_billing_currencies,id',
      'ap_vehicle_movement_id' => 'nullable|integer|exists:ap_vehicle_movement,id',
      'origin_entity_type' => 'nullable|string|max:100',
      'origin_entity_id' => 'nullable|integer',
      'created_by' => 'nullable|integer|exists:users,id',

      // Búsqueda general
      'search' => 'nullable|string|max:100',
    ];
  }


  public function attributes()
  {
    return [
      'ap_billing_document_type_id' => 'tipo de documento',
      'serie' => 'serie',
      'numero' => 'número',
      'area_id' => 'área',
      'status' => 'estado',
      'aceptada_por_sunat' => 'aceptada por SUNAT',
      'anulado' => 'anulado',
      'fecha_de_emision_desde' => 'fecha de emisión desde',
      'fecha_de_emision_hasta' => 'fecha de emisión hasta',
      'cliente_numero_de_documento' => 'número de documento del cliente',
      'cliente_denominacion' => 'denominación del cliente',
      'ap_billing_currency_id' => 'moneda',
      'ap_vehicle_movement_id' => 'movimiento de vehículo',
      'origin_entity_type' => 'tipo de entidad de origen',
      'origin_entity_id' => 'ID de entidad de origen',
      'created_by' => 'creado por',
    ];
  }


  /**
   * Get validated data with defaults.
   */
  public function validated($key = null, $default = null)
  {
    $validated = parent::validated($key, $default);

    // Establecer valores por defecto si no se proporcionaron
    $validated['page'] = $validated['page'] ?? 1;
    $validated['per_page'] = $validated['per_page'] ?? 15;
    $validated['sort_by'] = $validated['sort_by'] ?? 'id';
    $validated['sort_direction'] = $validated['sort_direction'] ?? 'desc';

    return $validated;
  }
}
