<?php

namespace App\Http\Resources\ap\facturacion;

use App\Http\Resources\ap\comercial\VehicleMovementResource;
use App\Http\Resources\ap\configuracionComercial\venta\ApBankResource;
use App\Http\Resources\gp\maestroGeneral\SunatConceptsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElectronicDocumentResource extends JsonResource
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
      'id' => $this->id,
      'sunat_concept_document_type_id' => $this->sunat_concept_document_type_id,
      'serie' => $this->serie,
      'series_id' => $this->series_id,
      'sede_id' => $this->seriesModel->sede_id,
      'sede' => $this->seriesModel->sede->abreviatura,
      'numero' => $this->numero,
      'full_number' => $this->full_number,
      'sunat_concept_transaction_type_id' => $this->sunat_concept_transaction_type_id,
      'area_id' => $this->area_id, // COMERCIAL O POSVENTA
      'origin_entity_type' => $this->origin_entity_type,
      'origin_entity_id' => $this->origin_entity_id,
      'ap_vehicle_movement_id' => $this->ap_vehicle_movement_id,
      'client_id' => $this->client_id,
      'purchase_request_quote_id' => $this->purchase_request_quote_id,
      'order_quotation_id' => $this->order_quotation_id,
      'work_order_id' => $this->work_order_id,
      'related_document_number' => $this->order_quotation_id
        ? $this->orderQuotation?->quotation_number
        : ($this->work_order_id ? $this->workOrder?->correlative : null),
      'related_document_type' => $this->order_quotation_id
        ? 'Cotización'
        : ($this->work_order_id ? 'Orden de Trabajo' : null),
      'advisor_name' => $this->order_quotation_id
        ? $this->orderQuotation?->createdBy?->name
        : ($this->work_order_id ? $this->workOrder?->advisor?->nombre_completo : null),
      'credit_note_id' => $creditNote?->id,
      'credit_note_number' => $creditNote?->full_number,
      'credit_note_type_id' => $creditNote?->sunat_concept_credit_note_type_id,
      'credit_note_total' => $creditNote ? (float)$creditNote->total : null,
      'debit_note_id' => $debitNote?->id,
      'debit_note_number' => $debitNote?->full_number,
      'debit_note_total' => $debitNote ? (float)$debitNote->total : null,
      'net_amount' => $this->net_amount,
      'sunat_concept_identity_document_type_id' => $this->sunat_concept_identity_document_type_id,
      'cliente_numero_de_documento' => $this->cliente_numero_de_documento,
      'cliente_denominacion' => $this->cliente_denominacion,
      'cliente_direccion' => $this->cliente_direccion,
      'cliente_email' => $this->cliente_email,
      'cliente_email_1' => $this->cliente_email_1,
      'cliente_email_2' => $this->cliente_email_2,
      'fecha_de_emision' => $this->fecha_de_emision,
      'fecha_de_vencimiento' => $this->fecha_de_vencimiento,
      'credit_days' => $this->credit_days,
      'sunat_concept_currency_id' => $this->sunat_concept_currency_id,
      'tipo_de_cambio' => (float)$this->tipo_de_cambio,
      'exchange_rate_id' => $this->exchange_rate_id,
      'porcentaje_de_igv' => $this->porcentaje_de_igv,
      'descuento_global' => (float)$this->descuento_global,
      'total_descuento' => (float)$this->total_descuento,
      'total_anticipo' => (float)$this->total_anticipo,
      'total_gravada' => (float)$this->total_gravada,
      'total_inafecta' => (float)$this->total_inafecta,
      'total_exonerada' => (float)$this->total_exonerada,
      'total_igv' => (float)$this->total_igv,
      'total_gratuita' => (float)$this->total_gratuita,
      'total_otros_cargos' => (float)$this->total_otros_cargos,
      'total_isc' => (float)$this->total_isc,
      'total' => (float)$this->total,
      'percepcion_tipo' => $this->percepcion_tipo,
      'percepcion_base_imponible' => $this->percepcion_base_imponible,
      'total_percepcion' => (float)$this->total_percepcion,
      'total_incluido_percepcion' => (float)$this->total_incluido_percepcion,
      'retencion_tipo' => $this->retencion_tipo,
      'retencion_base_imponible' => $this->retencion_base_imponible,
      'total_retencion' => (float)$this->total_retencion,
      'detraccion' => $this->detraccion,
      'sunat_concept_detraction_type_id' => $this->sunat_concept_detraction_type_id,
      'detraccion_total' => (float)$this->detraccion_total,
      'detraccion_porcentaje' => $this->detraccion_porcentaje,
      'medio_de_pago_detraccion' => $this->medio_de_pago_detraccion,
      'documento_que_se_modifica_tipo' => $this->documento_que_se_modifica_tipo,
      'documento_que_se_modifica_serie' => $this->documento_que_se_modifica_serie,
      'documento_que_se_modifica_numero' => $this->documento_que_se_modifica_numero,
      'original_document_id' => $this->original_document_id,
      'sunat_concept_credit_note_type_id' => $this->sunat_concept_credit_note_type_id,
      'sunat_concept_debit_note_type_id' => $this->sunat_concept_debit_note_type_id,
      'observaciones' => $this->observaciones,
      'condiciones_de_pago' => $this->condiciones_de_pago,
      'bank' => $this->bank ? ApBankResource::make($this->bank) : null,
      'operation_number' => $this->operation_number,
      'financing_type' => $this->financing_type,
      'medio_de_pago' => $this->medio_de_pago,
      'placa_vehiculo' => $this->placa_vehiculo,
      'orden_compra_servicio' => $this->orden_compra_servicio,
      'orden_compra_servicio_url' => $this->orden_compra_servicio_url,
      'codigo_unico' => $this->codigo_unico,
      'enviar_automaticamente_a_la_sunat' => $this->enviar_automaticamente_a_la_sunat,
      'enviar_automaticamente_al_cliente' => $this->enviar_automaticamente_al_cliente,
      'generado_por_contingencia' => $this->generado_por_contingencia,
      'enlace' => $this->enlace,
      'enlace_del_pdf' => $this->enlace_del_pdf,
      'enlace_del_xml' => $this->enlace_del_xml,
      'enlace_del_cdr' => $this->enlace_del_cdr,
      'aceptada_por_sunat' => $this->aceptada_por_sunat,
      'sunat_description' => $this->sunat_description,
      'sunat_note' => $this->sunat_note,
      'sunat_responsecode' => $this->sunat_responsecode,
      'sunat_soap_error' => $this->sunat_soap_error,
      'anulado' => $this->anulado,
      'cadena_para_codigo_qr' => $this->cadena_para_codigo_qr,
      'codigo_hash' => $this->codigo_hash,
      'status' => $this->status,
      'migration_status' => $this->migration_status,
      'was_dyn_requested' => $this->was_dyn_requested,
      'is_accounted' => $this->is_accounted,
      'is_annulled' => $this->is_annulled,
      'error_message' => $this->error_message,
      'is_advance_payment' => (boolean)$this->is_advance_payment,
      'is_referenced' => ($this->referencing_items_count ?? 0) > 0,
      'card_last4' => $this->card_last4,
      'internal_note' => $this->internal_note,
      'consolidation_type' => $this->consolidation_type,
      're_invoice' => $this->re_invoice,

      /**
       * Timestamps and users
       */
      'sent_at' => $this->sent_at,
      'migrated_at' => $this->migrated_at,
      'accepted_at' => $this->accepted_at,
      'cancelled_at' => $this->cancelled_at,
      'created_by' => $this->created_by,
      'updated_by' => $this->updated_by,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'deleted_at' => $this->deleted_at,

      /**
       * Relationships
       */
      'document_type' => SunatConceptsResource::make($this->documentType),
      'transaction_type' => SunatConceptsResource::make($this->transactionType),
      'identity_document_type' => SunatConceptsResource::make($this->identityDocumentType),
      'currency' => SunatConceptsResource::make($this->currency),
      'items' => ElectronicDocumentItemResource::collection($this->items),
      'guides' => ElectronicDocumentGuideResource::collection($this->guides),
      'installments' => ElectronicDocumentInstallmentResource::collection($this->installments),
      'vehicle_movement' => VehicleMovementResource::make($this->vehicleMovement),

      /**
       * Datos enriquecidos para el detalle (nullable, sólo poblados en show())
       */
      'creator_name' => $this->creator?->name,
      'updater_name' => $this->updater?->name,
      'sede_shop' => $this->seriesModel?->sede?->shop?->description,
      'sede_abrev' => $this->seriesModel?->sede?->abreviatura ?? $this->seriesModel?->sede?->suc_abrev,
      'exchange_rate' => $this->exchangeRate ? [
        'id' => $this->exchangeRate->id,
        'date' => $this->exchangeRate->date,
        'type' => $this->exchangeRate->type,
        'rate' => (float) $this->exchangeRate->rate,
      ] : null,
      'vehicle' => $this->vehiclePayload(),
      'purchase_request_quote' => $this->purchaseRequestQuote ? [
        'id' => $this->purchaseRequestQuote->id,
        'correlative' => $this->purchaseRequestQuote->correlative,
        'internal_code' => $this->purchaseRequestQuote->internal_code,
        'sale_price' => (float) $this->purchaseRequestQuote->sale_price,
        'base_selling_price' => (float) $this->purchaseRequestQuote->base_selling_price,
        'down_payment' => (float) $this->purchaseRequestQuote->down_payment,
        'opportunity_code' => $this->purchaseRequestQuote->opportunity?->opportunity_code,
        'advisor' => $this->purchaseRequestQuote->opportunity?->worker?->nombre_completo,
        'holder' => $this->purchaseRequestQuote->holder?->full_name,
      ] : null,
      'order_quotation' => $this->orderQuotation ? [
        'id' => $this->orderQuotation->id,
        'number' => $this->orderQuotation->quotation_number ?? $this->orderQuotation->code,
      ] : null,
      'work_order' => $this->workOrder ? [
        'id' => $this->workOrder->id,
        'number' => $this->workOrder->correlative ?? $this->workOrder->workorder_number,
      ] : null,
      'original_document' => $this->originalDocument ? [
        'id' => $this->originalDocument->id,
        'full_number' => $this->originalDocument->full_number,
        'document_type' => $this->originalDocument->documentType?->description,
        'total' => (float) $this->originalDocument->total,
      ] : null,
    ];
  }

  /**
   * Resuelve el vehículo asociado al documento a partir del movimiento de
   * inventario o, en su defecto, de la cotización / documento original.
   */
  private function vehiclePayload(): ?array
  {
    $vehicle = $this->vehicle
      ?? $this->purchaseRequestQuote?->vehicle
      ?? $this->originalDocument?->vehicle
      ?? $this->vehicleMovement?->vehicle;

    if (! $vehicle) {
      return null;
    }

    return [
      'id' => $vehicle->id,
      'vin' => $vehicle->vin,
      'plate' => $vehicle->plate,
      'engine_number' => $vehicle->engine_number,
      'year' => $vehicle->year,
      'mileage' => $vehicle->mileage,
      'color' => $vehicle->color?->description,
      'engine_type' => $vehicle->engineType?->description,
      'status' => $vehicle->vehicleStatus?->description,
      'status_color' => $vehicle->vehicleStatus?->color,
      'model_code' => $vehicle->model?->code,
      'model_version' => $vehicle->model?->version,
      'brand' => $vehicle->model?->family?->brand?->name,
      'family' => $vehicle->model?->family?->description,
      'warehouse' => $vehicle->warehousePhysical?->description,
    ];
  }
}
