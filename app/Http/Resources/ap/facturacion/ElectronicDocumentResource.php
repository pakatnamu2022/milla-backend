<?php

namespace App\Http\Resources\ap\facturacion;

use App\Http\Resources\ap\comercial\VehicleMovementResource;
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
    return [
      'id' => $this->id,
      'sunat_concept_document_type_id' => $this->sunat_concept_document_type_id,
      'serie' => $this->serie,
      'numero' => $this->numero,
      'full_number' => $this->full_number,
      'sunat_concept_transaction_type_id' => $this->sunat_concept_transaction_type_id,
      'origin_module' => $this->origin_module, // COMERCIAL O POSVENTA
      'origin_entity_type' => $this->origin_entity_type,
      'origin_entity_id' => $this->origin_entity_id,
      'ap_vehicle_movement_id' => $this->ap_vehicle_movement_id,
      'client_id' => $this->client_id,
      'purchase_request_quote_id' => $this->purchase_request_quote_id,
      'credit_note_id' => $this->credit_note_id,
      'debit_note_id' => $this->debit_note_id,
      'sunat_concept_identity_document_type_id' => $this->sunat_concept_identity_document_type_id,
      'cliente_numero_de_documento' => $this->cliente_numero_de_documento,
      'cliente_denominacion' => $this->cliente_denominacion,
      'cliente_direccion' => $this->cliente_direccion,
      'cliente_email' => $this->cliente_email,
      'cliente_email_1' => $this->cliente_email_1,
      'cliente_email_2' => $this->cliente_email_2,
      'fecha_de_emision' => $this->fecha_de_emision,
      'fecha_de_vencimiento' => $this->fecha_de_vencimiento,
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
      'sunat_concept_credit_note_type_id' => $this->sunat_concept_credit_note_type_id,
      'sunat_concept_debit_note_type_id' => $this->sunat_concept_debit_note_type_id,
      'observaciones' => $this->observaciones,
      'condiciones_de_pago' => $this->condiciones_de_pago,
      'medio_de_pago' => $this->medio_de_pago,
      'placa_vehiculo' => $this->placa_vehiculo,
      'orden_compra_servicio' => $this->orden_compra_servicio,
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
      'error_message' => $this->error_message,

      /**
       * Timestamps and users
       */
      'sent_at' => $this->sent_at,
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
      'vehicle_movement' => VehicleMovementResource::make($this->vehicleMovement)

    ];
  }
}
