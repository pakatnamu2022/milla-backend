<?php

namespace App\Http\Resources\ap\facturacion;

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
    return parent::toArray($request);
//    return [
//      'id' => $this->id,
//
//      // Tipo de documento y serie
//      'sunat_concept_document_type_id' => $this->sunat_concept_document_type_id,
//      'document_type' => new SunatConceptsResource($this->documentType),
//      'serie' => $this->serie,
//      'numero' => $this->numero,
//      'numero_completo' => $this->document_number,
//
//      // Tipo de operación
//      'sunat_concept_transaction_type_id' => $this->sunat_concept_transaction_type_id,
//      'transaction_type' => new SunatConceptsResource($this->transactionType),
//
//      // Origen del documento
//      'origin_module' => $this->origin_module,
//      'origin_entity_type' => $this->origin_entity_type,
//      'origin_entity_id' => $this->origin_entity_id,
//      'ap_vehicle_movement_id' => $this->ap_vehicle_movement_id,
//      'vehicle_movement' => $this->vehicleMovement,
//
//      // Datos del cliente
//      'sunat_concept_identity_document_type_id' => $this->sunat_concept_identity_document_type_id,
//      'identity_document_type' => new SunatConceptsResource($this->identityDocumentType),
//      'cliente_numero_de_documento' => $this->cliente_numero_de_documento,
//      'cliente_denominacion' => $this->cliente_denominacion,
//      'cliente_direccion' => $this->cliente_direccion,
//      'cliente_email' => $this->cliente_email,
//      'cliente_email_1' => $this->cliente_email_1,
//      'cliente_email_2' => $this->cliente_email_2,
//
//      // Fechas
//      'fecha_de_emision' => $this->fecha_de_emision?->format('Y-m-d'),
//      'fecha_de_vencimiento' => $this->fecha_de_vencimiento?->format('Y-m-d'),
//
//      // Moneda
//      'sunat_concept_currency_id' => $this->sunat_concept_currency_id,
//      'currency' => new SunatConceptsResource($this->currency),
//      'tipo_de_cambio' => $this->tipo_de_cambio,
//      'porcentaje_de_igv' => $this->porcentaje_de_igv,
//
//      // Totales
//      'descuento_global' => $this->descuento_global,
//      'total_descuento' => $this->total_descuento,
//      'total_anticipo' => $this->total_anticipo,
//      'total_gravada' => $this->total_gravada,
//      'total_inafecta' => $this->total_inafecta,
//      'total_exonerada' => $this->total_exonerada,
//      'total_igv' => $this->total_igv,
//      'total_gratuita' => $this->total_gratuita,
//      'total_otros_cargos' => $this->total_otros_cargos,
//      'total_isc' => $this->total_isc,
//      'total' => $this->total,
//
//      // Percepción
//      'percepcion_tipo' => $this->percepcion_tipo,
//      'percepcion_base_imponible' => $this->percepcion_base_imponible,
//      'total_percepcion' => $this->total_percepcion,
//      'total_incluido_percepcion' => $this->total_incluido_percepcion,
//
//      // Retención
//      'retencion_tipo' => $this->retencion_tipo,
//      'retencion_base_imponible' => $this->retencion_base_imponible,
//      'total_retencion' => $this->total_retencion,
//
//      // Detracción
//      'detraccion' => $this->detraccion,
//      'sunat_concept_detraction_type_id' => $this->sunat_concept_detraction_type_id,
//      'detraction_type' => new SunatConceptsResource($this->detractionType),
//      'detraccion_total' => $this->detraccion_total,
//      'detraccion_porcentaje' => $this->detraccion_porcentaje,
//      'medio_de_pago_detraccion' => $this->medio_de_pago_detraccion,
//
//      // Notas de crédito/débito
//      'documento_que_se_modifica_tipo' => $this->documento_que_se_modifica_tipo,
//      'documento_que_se_modifica_serie' => $this->documento_que_se_modifica_serie,
//      'documento_que_se_modifica_numero' => $this->documento_que_se_modifica_numero,
//      'sunat_concept_credit_note_type_id' => $this->sunat_concept_credit_note_type_id,
//      'credit_note_type' => new SunatConceptsResource($this->creditNoteType),
//      'sunat_concept_debit_note_type_id' => $this->sunat_concept_debit_note_type_id,
//      'debit_note_type' => new SunatConceptsResource($this->debitNoteType),
//
//      // Campos opcionales
//      'observaciones' => $this->observaciones,
//      'condiciones_de_pago' => $this->condiciones_de_pago,
//      'medio_de_pago' => $this->medio_de_pago,
//      'placa_vehiculo' => $this->placa_vehiculo,
//      'orden_compra_servicio' => $this->orden_compra_servicio,
//      'codigo_unico' => $this->codigo_unico,
//
//      // Configuración
//      'enviar_automaticamente_a_la_sunat' => $this->enviar_automaticamente_a_la_sunat,
//      'enviar_automaticamente_al_cliente' => $this->enviar_automaticamente_al_cliente,
//      'generado_por_contingencia' => $this->generado_por_contingencia,
//
//      // Enlaces de archivos
//      'enlace' => $this->enlace,
//      'enlace_del_pdf' => $this->enlace_del_pdf,
//      'enlace_del_xml' => $this->enlace_del_xml,
//      'enlace_del_cdr' => $this->enlace_del_cdr,
//
//      // Estado SUNAT
//      'aceptada_por_sunat' => $this->aceptada_por_sunat,
//      'sunat_description' => $this->sunat_description,
//      'sunat_note' => $this->sunat_note,
//      'sunat_responsecode' => $this->sunat_responsecode,
//      'sunat_soap_error' => $this->sunat_soap_error,
//      'anulado' => $this->anulado,
//      'cadena_para_codigo_qr' => $this->cadena_para_codigo_qr,
//      'codigo_hash' => $this->codigo_hash,
//
//      // Estado y mensajes
//      'status' => $this->status,
//      'error_message' => $this->error_message,
//
//      // Timestamps
//      'sent_at' => $this->sent_at?->format('Y-m-d H:i:s'),
//      'accepted_at' => $this->accepted_at?->format('Y-m-d H:i:s'),
//      'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
//      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
//      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
//
//      // Relaciones
//      'items' => ElectronicDocumentItemResource::collection($this->items),
//      'guides' => ElectronicDocumentGuideResource::collection($this->guides),
//      'installments' => ElectronicDocumentInstallmentResource::collection($this->installments),
//
//      // Atributos computados
//      'is_factura' => $this->is_factura,
//      'is_boleta' => $this->is_boleta,
//      'is_nota_credito' => $this->is_nota_credito,
//      'is_nota_debito' => $this->is_nota_debito,
//      'is_accepted' => $this->is_accepted,
//      'is_pending' => $this->is_pending,
//      'is_rejected' => $this->is_rejected,
//      'is_cancelled' => $this->is_cancelled,
//      'is_anticipo' => $this->isAnticipo(),
//
//      // Auditoría
//      'created_by' => $this->created_by,
//      'updated_by' => $this->updated_by,
//      'creator' => $this->creator,
//      'updater' => $this->updater,
//    ];
  }
}
