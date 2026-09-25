<?php

namespace App\Models\ap\facturacion;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de comunicaciones de baja (anulaciones) en Nubefact.
 * Una fila por operación: generar_anulacion / consultar_anulacion.
 */
class ElectronicDocumentCancellation extends Model
{
  protected $table = 'ap_billing_electronic_document_cancellations';

  const OPERATION_GENERATE = 'generar_anulacion';
  const OPERATION_QUERY = 'consultar_anulacion';

  /** Nubefact: "El documento indicado no existe o no fue enviado a NubeFacT" */
  const ERROR_CODE_NOT_FOUND = 24;

  protected $fillable = [
    'ap_billing_electronic_document_id',
    'operation',
    'motivo',
    'codigo_unico',
    'success',
    'http_status_code',
    'error_code',
    'error_message',
    'numero',
    'enlace',
    'sunat_ticket_numero',
    'aceptada_por_sunat',
    'sunat_description',
    'sunat_note',
    'sunat_responsecode',
    'sunat_soap_error',
    'enlace_del_pdf',
    'enlace_del_xml',
    'enlace_del_cdr',
    'request_payload',
    'response_payload',
    'user_id',
  ];

  protected $casts = [
    'success' => 'boolean',
    'aceptada_por_sunat' => 'boolean',
    'request_payload' => 'array',
    'response_payload' => 'array',
  ];

  public function electronicDocument(): BelongsTo
  {
    return $this->belongsTo(ElectronicDocument::class, 'ap_billing_electronic_document_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Guarda el resultado de NubefactApiService (cancelDocument / queryCancellation).
   * - generar_anulacion: una fila por envío (historial de bajas enviadas).
   * - consultar_anulacion: una sola fila por documento, que se actualiza con la última consulta.
   */
  public static function fromNubefactResult(
    ElectronicDocument $document,
    string $operation,
    array $result,
    ?string $motivo = null
  ): self {
    $data = is_array($result['data'] ?? null) ? $result['data'] : [];
    $error = $result['error'] ?? null;

    $attributes = [
      'motivo' => $motivo,
      'codigo_unico' => $result['request']['codigo_unico'] ?? null,
      'success' => (bool)($result['success'] ?? false),
      'http_status_code' => $result['http_status'] ?? null,
      'error_code' => isset($data['codigo']) ? (int)$data['codigo'] : null,
      'error_message' => $error === null ? null : (is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE) : (string)$error),
      'numero' => isset($data['numero']) && is_numeric($data['numero']) ? (int)$data['numero'] : null,
      'enlace' => $data['enlace'] ?? null,
      'sunat_ticket_numero' => $data['sunat_ticket_numero'] ?? null,
      'aceptada_por_sunat' => array_key_exists('aceptada_por_sunat', $data) ? (bool)$data['aceptada_por_sunat'] : null,
      'sunat_description' => $data['sunat_description'] ?? null,
      'sunat_note' => $data['sunat_note'] ?? null,
      'sunat_responsecode' => isset($data['sunat_responsecode']) ? (string)$data['sunat_responsecode'] : null,
      'sunat_soap_error' => $data['sunat_soap_error'] ?? null,
      'enlace_del_pdf' => $data['enlace_del_pdf'] ?? null,
      'enlace_del_xml' => $data['enlace_del_xml'] ?? null,
      'enlace_del_cdr' => $data['enlace_del_cdr'] ?? null,
      // No guardar los base64 (pueden ser pesados)
      'request_payload' => $result['request'] ?? null,
      'response_payload' => array_diff_key($data, array_flip(['xml_zip_base64', 'pdf_zip_base64', 'cdr_zip_base64'])),
      'user_id' => auth()->id(),
    ];

    $key = [
      'ap_billing_electronic_document_id' => $document->id,
      'operation' => $operation,
    ];

    if ($operation === self::OPERATION_QUERY) {
      // Conservar el motivo de una consulta previa si esta no trae uno
      if ($motivo === null) {
        unset($attributes['motivo']);
      }
      $record = self::firstOrNew($key);
      $record->fill($attributes);
      $record->updateTimestamps(); // refleja la fecha de la última consulta aunque la respuesta no cambie
      $record->save();
      return $record;
    }

    return self::create($key + $attributes);
  }
}
