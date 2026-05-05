<?php

namespace App\Http\Requests\ap\facturacion;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ElectronicDocumentReportRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      // Filtros básicos
      'full_number' => 'nullable|string',
      'serie' => 'nullable|string',
      'numero' => 'nullable|integer',
      'sunat_concept_document_type_id' => 'nullable|integer|exists:gp_sunat_concepts,id',
      'status' => 'nullable|string|in:draft,sent,accepted,rejected,cancelled',
      'aceptada_por_sunat' => 'nullable|boolean',
      'anulado' => 'nullable|boolean',

      // Filtros de fechas
      'fecha_de_emision' => 'nullable|date',
      'fecha_de_emision_from' => 'nullable|date',
      'fecha_de_emision_to' => 'nullable|date|after_or_equal:fecha_de_emision_from',
      'fecha_de_vencimiento' => 'nullable|date',
      'fecha_de_vencimiento_from' => 'nullable|date',
      'fecha_de_vencimiento_to' => 'nullable|date|after_or_equal:fecha_de_vencimiento_from',

      // Filtros de cliente
      'cliente_numero_de_documento' => 'nullable|string',
      'cliente_denominacion' => 'nullable|string',
      'client_id' => 'nullable|integer|exists:ap_business_partners,id',
      'sunat_concept_identity_document_type_id' => 'nullable|integer|exists:gp_sunat_concepts,id',

      // Filtros de origen
//      'area_id' => 'nullable|string|in:comercial,posventa',
      'area_id' => [
        'nullable',
        'string',
        Rule::in(ApMasters::ALL_AREAS)
      ],
      'origin_entity_type' => 'nullable|string',
      'origin_entity_id' => 'nullable|integer',

      // Filtros de referencias
      'purchase_request_quote_id' => 'nullable|integer|exists:ap_purchase_request_quotes,id',
      'order_quotation_id' => 'nullable|integer|exists:ap_order_quotations,id',
      'work_order_id' => 'nullable|integer|exists:ap_work_orders,id',
      'ap_vehicle_movement_id' => 'nullable|integer|exists:ap_vehicle_movements,id',
      'original_document_id' => 'nullable|integer|exists:ap_billing_electronic_documents,id',

      // Filtros de moneda y montos
      'sunat_concept_currency_id' => 'nullable|integer|exists:gp_sunat_concepts,id',
      'total_min' => 'nullable|numeric|min:0',
      'total_max' => 'nullable|numeric|min:0|gte:total_min',

      // Filtro de anticipo
      'is_advance_payment' => 'nullable|boolean',

      // Filtro de creador
      'created_by' => 'nullable|integer|exists:users,id',

      // Filtros de serie
      'series_id' => 'nullable|integer|exists:gp_assign_sales_series,id',

      // Búsqueda general
      'search' => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'full_number' => 'número completo',
      'serie' => 'serie',
      'numero' => 'número',
      'sunat_concept_document_type_id' => 'tipo de documento',
      'status' => 'estado',
      'aceptada_por_sunat' => 'aceptada por SUNAT',
      'anulado' => 'anulado',
      'fecha_de_emision' => 'fecha de emisión',
      'fecha_de_emision_from' => 'fecha de emisión desde',
      'fecha_de_emision_to' => 'fecha de emisión hasta',
      'fecha_de_vencimiento' => 'fecha de vencimiento',
      'fecha_de_vencimiento_from' => 'fecha de vencimiento desde',
      'fecha_de_vencimiento_to' => 'fecha de vencimiento hasta',
      'cliente_numero_de_documento' => 'número de documento del cliente',
      'cliente_denominacion' => 'denominación del cliente',
      'client_id' => 'cliente',
      'sunat_concept_identity_document_type_id' => 'tipo de documento de identidad del cliente',
      'area_id' => 'área de origen',
      'origin_entity_type' => 'tipo de entidad de origen',
      'origin_entity_id' => 'ID de entidad de origen',
      'purchase_request_quote_id' => 'cotización de solicitud de compra',
      'order_quotation_id' => 'cotización de pedido',
      'work_order_id' => 'orden de trabajo',
      'ap_vehicle_movement_id' => 'movimiento vehicular',
      'original_document_id' => 'documento original',
      'sunat_concept_currency_id' => 'moneda',
      'total_min' => 'total mínimo',
      'total_max' => 'total máximo',
      'is_advance_payment' => 'es anticipo',
      'created_by' => 'creado por',
      'series_id' => 'serie asignada'
    ];
  }

  /**
   * Convierte los parámetros del request a filtros para el trait Reportable
   */
  public function toReportFilters(): array
  {
    $filters = [];

    // Filtros directos
    if ($this->filled('full_number')) {
      $filters[] = [
        'column' => 'full_number',
        'operator' => 'like',
        'value' => $this->input('full_number'),
      ];
    }

    if ($this->filled('serie')) {
      $filters[] = [
        'column' => 'serie',
        'operator' => '=',
        'value' => $this->input('serie'),
      ];
    }

    if ($this->filled('numero')) {
      $filters[] = [
        'column' => 'numero',
        'operator' => '=',
        'value' => $this->input('numero'),
      ];
    }

    if ($this->filled('sunat_concept_document_type_id')) {
      $filters[] = [
        'column' => 'sunat_concept_document_type_id',
        'operator' => '=',
        'value' => $this->input('sunat_concept_document_type_id'),
      ];
    }

    if ($this->filled('status')) {
      $filters[] = [
        'column' => 'status',
        'operator' => '=',
        'value' => $this->input('status'),
      ];
    }

    if ($this->filled('aceptada_por_sunat')) {
      $filters[] = [
        'column' => 'aceptada_por_sunat',
        'operator' => '=',
        'value' => $this->input('aceptada_por_sunat'),
      ];
    }

    if ($this->filled('anulado')) {
      $filters[] = [
        'column' => 'anulado',
        'operator' => '=',
        'value' => $this->input('anulado'),
      ];
    }

    // Filtros de fecha
    if ($this->filled('fecha_de_emision')) {
      $filters[] = [
        'column' => 'fecha_de_emision',
        'operator' => '=',
        'value' => $this->input('fecha_de_emision'),
      ];
    } elseif ($this->filled('fecha_de_emision_from') && $this->filled('fecha_de_emision_to')) {
      $filters[] = [
        'column' => 'fecha_de_emision',
        'operator' => 'date_between',
        'value' => [$this->input('fecha_de_emision_from'), $this->input('fecha_de_emision_to')],
      ];
    } elseif ($this->filled('fecha_de_emision_from')) {
      $filters[] = [
        'column' => 'fecha_de_emision',
        'operator' => '>=',
        'value' => $this->input('fecha_de_emision_from'),
      ];
    } elseif ($this->filled('fecha_de_emision_to')) {
      $filters[] = [
        'column' => 'fecha_de_emision',
        'operator' => '<=',
        'value' => $this->input('fecha_de_emision_to'),
      ];
    }

    if ($this->filled('fecha_de_vencimiento')) {
      $filters[] = [
        'column' => 'fecha_de_vencimiento',
        'operator' => '=',
        'value' => $this->input('fecha_de_vencimiento'),
      ];
    } elseif ($this->filled('fecha_de_vencimiento_from') && $this->filled('fecha_de_vencimiento_to')) {
      $filters[] = [
        'column' => 'fecha_de_vencimiento',
        'operator' => 'date_between',
        'value' => [$this->input('fecha_de_vencimiento_from'), $this->input('fecha_de_vencimiento_to')],
      ];
    } elseif ($this->filled('fecha_de_vencimiento_from')) {
      $filters[] = [
        'column' => 'fecha_de_vencimiento',
        'operator' => '>=',
        'value' => $this->input('fecha_de_vencimiento_from'),
      ];
    } elseif ($this->filled('fecha_de_vencimiento_to')) {
      $filters[] = [
        'column' => 'fecha_de_vencimiento',
        'operator' => '<=',
        'value' => $this->input('fecha_de_vencimiento_to'),
      ];
    }

    // Filtros de cliente
    if ($this->filled('cliente_numero_de_documento')) {
      $filters[] = [
        'column' => 'cliente_numero_de_documento',
        'operator' => 'like',
        'value' => $this->input('cliente_numero_de_documento'),
      ];
    }

    if ($this->filled('cliente_denominacion')) {
      $filters[] = [
        'column' => 'cliente_denominacion',
        'operator' => 'like',
        'value' => $this->input('cliente_denominacion'),
      ];
    }

    if ($this->filled('client_id')) {
      $filters[] = [
        'column' => 'client_id',
        'operator' => '=',
        'value' => $this->input('client_id'),
      ];
    }

    if ($this->filled('sunat_concept_identity_document_type_id')) {
      $filters[] = [
        'column' => 'sunat_concept_identity_document_type_id',
        'operator' => '=',
        'value' => $this->input('sunat_concept_identity_document_type_id'),
      ];
    }

    // Filtros de origen
    if ($this->filled('area_id')) {
      $filters[] = [
        'column' => 'area_id',
        'operator' => '=',
        'value' => $this->input('area_id'),
      ];
    }

    if ($this->filled('origin_entity_type')) {
      $filters[] = [
        'column' => 'origin_entity_type',
        'operator' => '=',
        'value' => $this->input('origin_entity_type'),
      ];
    }

    if ($this->filled('origin_entity_id')) {
      $filters[] = [
        'column' => 'origin_entity_id',
        'operator' => '=',
        'value' => $this->input('origin_entity_id'),
      ];
    }

    // Filtros de referencias
    if ($this->filled('purchase_request_quote_id')) {
      $filters[] = [
        'column' => 'purchase_request_quote_id',
        'operator' => '=',
        'value' => $this->input('purchase_request_quote_id'),
      ];
    }

    if ($this->filled('order_quotation_id')) {
      $filters[] = [
        'column' => 'order_quotation_id',
        'operator' => '=',
        'value' => $this->input('order_quotation_id'),
      ];
    }

    if ($this->filled('work_order_id')) {
      $filters[] = [
        'column' => 'work_order_id',
        'operator' => '=',
        'value' => $this->input('work_order_id'),
      ];
    }

    if ($this->filled('ap_vehicle_movement_id')) {
      $filters[] = [
        'column' => 'ap_vehicle_movement_id',
        'operator' => '=',
        'value' => $this->input('ap_vehicle_movement_id'),
      ];
    }

    if ($this->filled('original_document_id')) {
      $filters[] = [
        'column' => 'original_document_id',
        'operator' => '=',
        'value' => $this->input('original_document_id'),
      ];
    }

    // Filtros de moneda y montos
    if ($this->filled('sunat_concept_currency_id')) {
      $filters[] = [
        'column' => 'sunat_concept_currency_id',
        'operator' => '=',
        'value' => $this->input('sunat_concept_currency_id'),
      ];
    }

    if ($this->filled('total_min') && $this->filled('total_max')) {
      $filters[] = [
        'column' => 'total',
        'operator' => 'between',
        'value' => [$this->input('total_min'), $this->input('total_max')],
      ];
    } elseif ($this->filled('total_min')) {
      $filters[] = [
        'column' => 'total',
        'operator' => '>=',
        'value' => $this->input('total_min'),
      ];
    } elseif ($this->filled('total_max')) {
      $filters[] = [
        'column' => 'total',
        'operator' => '<=',
        'value' => $this->input('total_max'),
      ];
    }

    // Filtro de anticipo
    if ($this->filled('is_advance_payment')) {
      $filters[] = [
        'column' => 'is_advance_payment',
        'operator' => '=',
        'value' => $this->input('is_advance_payment'),
      ];
    }

    // Filtro de creador
    if ($this->filled('created_by')) {
      $filters[] = [
        'column' => 'created_by',
        'operator' => '=',
        'value' => $this->input('created_by'),
      ];
    }

    // Filtro de serie
    if ($this->filled('series_id')) {
      $filters[] = [
        'column' => 'series_id',
        'operator' => '=',
        'value' => $this->input('series_id'),
      ];
    }

    return $filters;
  }
}
