<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\IndexRequest;
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
      'origin_module' => [
        'nullable',
        'string',
        Rule::in(['comercial', 'posventa'])
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

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'page.integer' => 'El número de página debe ser un número entero',
      'page.min' => 'El número de página debe ser mayor o igual a 1',
      'per_page.integer' => 'La cantidad de registros por página debe ser un número entero',
      'per_page.min' => 'La cantidad de registros por página debe ser mayor o igual a 1',
      'per_page.max' => 'La cantidad de registros por página no puede exceder 100',
      'sort_by.in' => 'El campo de ordenamiento no es válido',
      'sort_direction.in' => 'La dirección de ordenamiento debe ser "asc" o "desc"',
      'ap_billing_document_type_id.exists' => 'El tipo de documento seleccionado no es válido',
      'origin_module.in' => 'El módulo de origen debe ser "comercial" o "posventa"',
      'status.in' => 'El estado seleccionado no es válido',
      'fecha_de_emision_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde',
      'ap_billing_currency_id.exists' => 'La moneda seleccionada no es válida',
      'ap_vehicle_movement_id.exists' => 'El movimiento de vehículo seleccionado no es válido',
      'created_by.exists' => 'El usuario seleccionado no es válido',
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
