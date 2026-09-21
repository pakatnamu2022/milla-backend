<?php

namespace App\Http\Resources\ap\facturacion;

use App\Http\Resources\gp\maestroGeneral\SunatConceptsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource simplificado para listar documentos electrónicos
 * Solo incluye los campos necesarios para la tabla de listado
 */
class ElectronicDocumentListResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $creditNote = ($this->creditNote && !$this->creditNote->anulado) ? $this->creditNote : null;
    $debitNote = ($this->debitNote && !$this->debitNote->anulado) ? $this->debitNote : null;

    return [
      // IDs principales
      'id' => $this->id,
      'sunat_concept_document_type_id' => $this->sunat_concept_document_type_id,
      'original_document_id' => $this->original_document_id,

      // Información del documento
      'full_number' => $this->full_number,
      'is_advance_payment' => (boolean)$this->is_advance_payment,

      // Cliente
      'cliente_denominacion' => $this->cliente_denominacion,
      'cliente_numero_de_documento' => $this->cliente_numero_de_documento,

      // Fechas
      'fecha_de_emision' => $this->fecha_de_emision,

      // Montos
      'total' => (float)$this->total,

      // Estados
      'status' => $this->status,
      'aceptada_por_sunat' => $this->aceptada_por_sunat,
      'sunat_description' => $this->sunat_description,
      'migration_status' => $this->migration_status,
      'is_accounted' => $this->is_accounted,
      'anulado' => $this->anulado,
      'is_annulled' => $this->is_annulled,
      'has_product_traverse' => $this->has_product_traverse,
      'associate_purchase_traverse' => $this->associate_purchase_traverse,

      // Área y sede
      'area_id' => $this->area_id,
      'sede' => $this->seriesModel->sede->abreviatura,

      // Documento relacionado (para área no comercial)
      'related_document_number' => $this->order_quotation_id
        ? $this->orderQuotation?->quotation_number
        : ($this->work_order_id ? $this->workOrder?->correlative : null),
      'related_document_type' => $this->order_quotation_id
        ? 'Cotización'
        : ($this->work_order_id ? 'Orden de Trabajo' : null),

      // Asesor (para área no comercial)
      'advisor_name' => $this->order_quotation_id
        ? $this->orderQuotation?->createdBy?->name
        : ($this->work_order_id ? $this->workOrder?->advisor?->nombre_completo : null),

      // Nota interna
      'internal_note' => $this->internal_note,

      // Notas de crédito/débito
      'is_referenced' => ($this->referencing_items_count ?? 0) > 0,
      'credit_note_id' => $creditNote?->id,
      'debit_note_id' => $debitNote?->id,

      // Tipo de consolidación
      'consolidation_type' => $this->consolidation_type,

      // Relaciones simplificadas (solo datos necesarios)
      'document_type' => $this->documentType ? [
        'id' => $this->documentType->id,
        'description' => $this->documentType->description,
      ] : null,
      'currency' => $this->currency ? [
        'id' => $this->currency->id,
        'iso_code' => $this->currency->iso_code,
      ] : null,
    ];
  }
}
