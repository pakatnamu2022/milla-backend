<?php

namespace App\Http\Services\ap\facturacion;

use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NubefactApiService
{
  protected string $apiUrl;
  protected string $token;
  protected string $ruc;

  public function __construct()
  {
  }

  public function setApiCredentials($sedeId): void
  {
    $this->apiUrl = config('nubefact.' . $sedeId . '.api_url');
    $this->token = config('nubefact.' . $sedeId . '.token');
    $this->ruc = config('nubefact.' . $sedeId . '.ruc');
  }

  /**
   * Genera y envía un comprobante electrónico a SUNAT mediante Nubefact
   *
   * @param object $document El documento de facturación (ApBillingElectronicDocument)
   * @return array La respuesta de Nubefact
   * @throws Exception
   */
  public function generateDocument($document): array
  {
    $this->setApiCredentials($document->sede_id);
    $payload = $this->buildDocumentPayload($document);
    $endpoint = $this->getEndpointForDocumentType($document->documentType->code_nubefact);

    // Log temporal para debugging
    Log::info('Payload enviado a Nubefact', [
      'document_id' => $document->id,
      'payload' => $payload,
    ]);

    $logData = [
      'ap_billing_electronic_document_id' => $document->id,
      'operation' => 'generar_comprobante',
      'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ];

    try {
      $response = Http::withHeaders([
        'Authorization' => 'Token token="' . $this->token . '"',
        'Content-Type' => 'application/json',
      ])->post($this->apiUrl . $endpoint, $payload);

      $responseData = $response->json();
      $httpStatusCode = $response->status();

      $logData['response_payload'] = json_encode($responseData, JSON_UNESCAPED_UNICODE);
      $logData['http_status_code'] = $httpStatusCode;

      // Log raw response for debugging
      Log::info('Nubefact API Response', [
        'status' => $httpStatusCode,
        'response' => $responseData,
        'endpoint' => $endpoint,
      ]);

      if ($response->successful() && isset($responseData['aceptada_por_sunat'])) {
        $logData['success'] = true;
        $this->logRequest($logData);

        return [
          'success' => true,
          'data' => $responseData,
        ];
      } else {
        $errorMessage = $responseData['errors'] ?? $responseData['message'] ?? 'Error desconocido';
        $logData['success'] = false;
        $logData['error_message'] = is_array($errorMessage) ? json_encode($errorMessage) : $errorMessage;
        $this->logRequest($logData);

        return [
          'success' => false,
          'error' => $errorMessage,
          'data' => $responseData,
        ];
      }
    } catch (Exception $e) {
      $logData['success'] = false;
      $logData['error_message'] = $e->getMessage();
      $logData['http_status_code'] = 0;
      $this->logRequest($logData);

      Log::error('Error en NubefactApiService::generateDocument', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }

  /**
   * Consulta el estado de un comprobante en Nubefact
   *
   * @param object $document El documento de facturación
   * @return array La respuesta de Nubefact
   * @throws Exception
   */
  public function queryDocument($document): array
  {
    $this->setApiCredentials($document->sede_id);
    $body = [
      'operacion' => 'consultar_comprobante',
      'tipo_de_comprobante' => $document->documentType->code_nubefact,
      'serie' => $document->serie,
      'numero' => $document->numero,
    ];

    $logData = [
      'ap_billing_electronic_document_id' => $document->id,
      'operation' => 'consultar_comprobante',
      'request_payload' => json_encode($body, JSON_UNESCAPED_UNICODE),
    ];

    try {
      $response = Http::withHeaders([
        'Authorization' => 'Token token="' . $this->token . '"',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ])->post($this->apiUrl, $body);

      $responseData = $response->json();
      $httpStatusCode = $response->status();
//
//      Log::info($this->apiUrl . $endpoint);
//      Log::info($body);
//      Log::info($httpStatusCode);
//      Log::info($responseData);


      $logData['response_payload'] = json_encode($responseData, JSON_UNESCAPED_UNICODE);
      $logData['http_status_code'] = $httpStatusCode;

      if ($httpStatusCode == 200) {
        $logData['success'] = true;
        $this->logRequest($logData);

        return [
          'success' => true,
          'data' => $responseData,
        ];
      } else {
        $errorMessage = $responseData['errors'] ?? $responseData['message'] ?? 'Error desconocido';
        $logData['success'] = false;
        $logData['error_message'] = is_array($errorMessage) ? json_encode($errorMessage) : $errorMessage;
        $this->logRequest($logData);

        throw new Exception($errorMessage);

//        return [
//          'success' => false,
//          'error' => $errorMessage,
//          'data' => $responseData,
//        ];
      }
    } catch (Exception $e) {
      $logData['success'] = false;
      $logData['error_message'] = $e->getMessage();
      $logData['http_status_code'] = 0;
      $this->logRequest($logData);

      Log::error('Error en NubefactApiService::queryDocument', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }

  /**
   * Anula un comprobante electrónico
   *
   * @param object $document El documento a anular
   * @param string $reason Motivo de la anulación
   * @return array La respuesta de Nubefact
   * @throws Exception
   */
  public function cancelDocument($document, string $reason): array
  {
    $this->setApiCredentials($document->sede_id);
    $payload = [
      'operacion' => 'generar_anulacion',
      'tipo_de_comprobante' => $document->documentType->code_nubefact,
      'serie' => $document->serie,
      'numero' => $document->numero,
      'motivo' => $reason,
      'codigo_unico' => $document->codigo_unico ?? uniqid('ANULACION_'),
    ];

    $logData = [
      'ap_billing_electronic_document_id' => $document->id,
      'operation' => 'generar_anulacion',
      'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ];

    try {
      $response = Http::withHeaders([
        'Authorization' => 'Token token="' . $this->token . '"',
        'Content-Type' => 'application/json',
      ])->post($this->apiUrl, $payload);

      $responseData = $response->json();
      $httpStatusCode = $response->status();

      $logData['response_payload'] = json_encode($responseData, JSON_UNESCAPED_UNICODE);
      $logData['http_status_code'] = $httpStatusCode;

      if ($response->successful() && isset($responseData['aceptada_por_sunat'])) {
        $logData['success'] = true;
        $this->logRequest($logData);

        return [
          'success' => true,
          'data' => $responseData,
        ];
      } else {
        $errorMessage = $responseData['errors'] ?? $responseData['message'] ?? 'Error desconocido';
        $logData['success'] = false;
        $logData['error_message'] = is_array($errorMessage) ? json_encode($errorMessage) : $errorMessage;
        $this->logRequest($logData);

        return [
          'success' => false,
          'error' => $errorMessage,
          'data' => $responseData,
        ];
      }
    } catch (Exception $e) {
      $logData['success'] = false;
      $logData['error_message'] = $e->getMessage();
      $logData['http_status_code'] = 0;
      $this->logRequest($logData);

      Log::error('Error en NubefactApiService::cancelDocument', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }

  /**
   * Consulta el estado de una anulación
   *
   * @param object $document El documento anulado
   * @return array La respuesta de Nubefact
   * @throws Exception
   */
  public function queryCancellation($document): array
  {
    $this->setApiCredentials($document->sede_id);
    $queryParams = [
      'tipo' => $document->documentType->code_nubefact,
      'serie' => $document->serie,
      'numero' => $document->numero,
    ];

    $logData = [
      'ap_billing_electronic_document_id' => $document->id,
      'operation' => 'consultar_anulacion',
      'request_payload' => json_encode($queryParams, JSON_UNESCAPED_UNICODE),
    ];

    try {
      $response = Http::withHeaders([
        'Authorization' => 'Token token="' . $this->token . '"',
        'Content-Type' => 'application/json',
      ])->get($this->apiUrl, $queryParams);

      $responseData = $response->json();
      $httpStatusCode = $response->status();

      $logData['response_payload'] = json_encode($responseData, JSON_UNESCAPED_UNICODE);
      $logData['http_status_code'] = $httpStatusCode;

      if ($response->successful()) {
        $logData['success'] = true;
        $this->logRequest($logData);

        return [
          'success' => true,
          'data' => $responseData,
        ];
      } else {
        $errorMessage = $responseData['errors'] ?? $responseData['message'] ?? 'Error desconocido';
        $logData['success'] = false;
        $logData['error_message'] = is_array($errorMessage) ? json_encode($errorMessage) : $errorMessage;
        $this->logRequest($logData);

        return [
          'success' => false,
          'error' => $errorMessage,
          'data' => $responseData,
        ];
      }
    } catch (Exception $e) {
      $logData['success'] = false;
      $logData['error_message'] = $e->getMessage();
      $logData['http_status_code'] = 0;
      $this->logRequest($logData);

      Log::error('Error en NubefactApiService::queryCancellation', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }

  /**
   * Construye el payload JSON para enviar a Nubefact según la documentación
   *
   * @param object $document El documento de facturación
   * @return array El payload formateado
   */
  public function buildDocumentPayload($document): array
  {
    $this->setApiCredentials($document->sede_id);
    $payload = [
      'operacion' => 'generar_comprobante',
      'tipo_de_comprobante' => $document->documentType->code_nubefact,
      'serie' => $document->serie,
      'numero' => $document->numero,
      'sunat_transaction' => $document->transactionType->code_nubefact,
      'cliente_tipo_de_documento' => $document->identityDocumentType->code_nubefact,
      'cliente_numero_de_documento' => $document->cliente_numero_de_documento,
      'cliente_denominacion' => $document->cliente_denominacion,
      'cliente_direccion' => $document->cliente_direccion,
      'cliente_email' => $document->cliente_email,
      'fecha_de_emision' => $document->fecha_de_emision->format('d-m-Y'),
      'moneda' => $document->currency->code_nubefact,
      'porcentaje_de_igv' => $document->porcentaje_de_igv,
      'total_descuento' => $document->total_descuento ?? 0,
      'total_anticipo' => $document->total_anticipo ?? 0,
      'total_gravada' => $document->total_gravada ?? 0,
      'total_inafecta' => $document->total_inafecta ?? 0,
      'total_exonerada' => $document->total_exonerada ?? 0,
      'total_igv' => $document->total_igv ?? 0,
      'total_gratuita' => $document->total_gratuita ?? 0,
      'total_otros_cargos' => $document->total_otros_cargos ?? 0,
      'total' => $document->total,
      'enviar_automaticamente_a_la_sunat' => $document->enviar_automaticamente_a_la_sunat,
      'enviar_automaticamente_al_cliente' => $document->enviar_automaticamente_al_cliente,
      'codigo_unico' => $document->codigo_unico ?? uniqid('DOC_'),
    ];

    // Agregar emails adicionales si existen
    if ($document->cliente_email_1) {
      $payload['cliente_email_1'] = $document->cliente_email_1;
    }
    if ($document->cliente_email_2) {
      $payload['cliente_email_2'] = $document->cliente_email_2;
    }

    // Fecha de vencimiento
    if ($document->fecha_de_vencimiento) {
      $payload['fecha_de_vencimiento'] = $document->fecha_de_vencimiento->format('d-m-Y');
    }

    // Tipo de cambio
    if ($document->tipo_de_cambio) {
      $payload['tipo_de_cambio'] = $document->tipo_de_cambio;
    }

    // Descuento global
    if ($document->descuento_global) {
      $payload['descuento_global'] = $document->descuento_global;
    }

    // Observaciones
    if ($document->observaciones) {
      $payload['observaciones'] = $document->observaciones;
    }

    // Condiciones de pago
    if ($document->condiciones_de_pago) {
      $payload['condiciones_de_pago'] = $document->condiciones_de_pago;
    }

    // Medio de pago
    if ($document->medio_de_pago) {
      $payload['medio_de_pago'] = $document->medio_de_pago;
    }

    // Placa de vehículo
    if ($document->placa_vehiculo) {
      $payload['placa_vehiculo'] = $document->placa_vehiculo;
    }

    // Orden de compra
    if ($document->orden_compra_servicio) {
      $payload['orden_compra_servicio'] = $document->orden_compra_servicio;
    }

    // Percepción
    if ($document->percepcion_tipo) {
      $payload['percepcion_tipo'] = $document->percepcion_tipo;
      $payload['percepcion_base_imponible'] = $document->percepcion_base_imponible;
      $payload['total_percepcion'] = $document->total_percepcion;
      $payload['total_incluido_percepcion'] = $document->total_incluido_percepcion;
    }

    // Retención
    if ($document->retencion_tipo) {
      $payload['retencion_tipo'] = $document->retencion_tipo;
      $payload['retencion_base_imponible'] = $document->retencion_base_imponible;
      $payload['total_retencion'] = $document->total_retencion;
    }

    // Detracción
    if ($document->detraccion) {
      $payload['detraccion'] = true;
      $payload['codigo_tipo_operacion'] = $document->detractionType->code_nubefact ?? null;
      $payload['detraccion_total'] = $document->detraccion_total;
      $payload['detraccion_porcentaje'] = $document->detraccion_porcentaje;

      if ($document->medio_de_pago_detraccion) {
        $payload['medio_de_pago_detraccion'] = $document->medio_de_pago_detraccion;
      }
    }

    // ISC
    if ($document->total_isc) {
      $payload['total_isc'] = $document->total_isc;
    }

    // Campos para Notas de Crédito
    if (in_array($document->documentType->code_nubefact, ['3'])) {
      $payload['documento_que_se_modifica_tipo'] = $document->documento_que_se_modifica_tipo;
      $payload['documento_que_se_modifica_serie'] = $document->documento_que_se_modifica_serie;
      $payload['documento_que_se_modifica_numero'] = $document->documento_que_se_modifica_numero;
      $payload['tipo_de_nota_de_credito'] = $document->creditNoteType->code_nubefact ?? null;
    }

    // Campos para Notas de Débito
    if (in_array($document->documentType->code_nubefact, ['4'])) {
      $payload['documento_que_se_modifica_tipo'] = $document->documento_que_se_modifica_tipo;
      $payload['documento_que_se_modifica_serie'] = $document->documento_que_se_modifica_serie;
      $payload['documento_que_se_modifica_numero'] = $document->documento_que_se_modifica_numero;
      $payload['tipo_de_nota_de_debito'] = $document->debitNoteType->code_nubefact ?? null;
    }

    // Items del comprobante
    $payload['items'] = [];
    $isAnticipo = $document->sunat_concept_transaction_type_id == SunatConcepts::ID_VENTA_INTERNA_ANTICIPOS;

    foreach ($document->items as $index => $item) {
      $tipoIgv = $item->igvType->code_nubefact;
      $igvItem = $item->igv;

      if ($isAnticipo) {
        // Para anticipos (sunat_transaction = 04):
        // - tipo_de_igv debe ser "1" (código simplificado de Nubefact para gravado)
        // - igv SÍ se calcula normalmente (18% del subtotal)
        // - El tributo 9996 se genera automáticamente por el sunat_transaction = 04
        $anticipoIgvType = SunatConcepts::find(SunatConcepts::ID_IGV_ANTICIPO_GRAVADO);
        $tipoIgv = $anticipoIgvType->code_nubefact; // "1"
        // $igvItem mantiene su valor calculado normalmente
      }

      $itemData = [
        'unidad_de_medida' => $item->unidad_de_medida,
        'codigo' => $item->accountPlan->code_dynamics,
        'descripcion' => $item->descripcion,
        'cantidad' => $item->cantidad,
        'valor_unitario' => $item->valor_unitario,
        'precio_unitario' => $item->precio_unitario,
        'descuento' => $item->descuento ?? 0,
        'subtotal' => $item->subtotal,
        'tipo_de_igv' => $tipoIgv,
        'igv' => $igvItem,
        'total' => $item->total,
        'anticipo_regularizacion' => $item->anticipo_regularizacion ? "true" : "false",
        'anticipo_documento_serie' => $item->anticipo_regularizacion ? $item->anticipo_documento_serie : "",
        'anticipo_documento_numero' => $item->anticipo_regularizacion ? $item->anticipo_documento_numero : "",
      ];

      // Código de producto SUNAT
      if ($item->codigo_producto_sunat) {
        $itemData['codigo_producto_sunat'] = $item->codigo_producto_sunat;
      }

      $payload['items'][] = $itemData;
    }

    // Guías de remisión
    if ($document->guides && $document->guides->count() > 0) {
      $payload['guia_remision'] = [];
      foreach ($document->guides as $guide) {
        $payload['guia_remision'][] = [
          'guia_tipo' => $guide->guia_tipo,
          'guia_serie_numero' => $guide->guia_serie_numero,
        ];
      }
    }

    // Cuotas (venta al crédito)
    if ($document->installments && $document->installments->count() > 0) {
      $payload['venta_al_credito'] = [];
      foreach ($document->installments as $installment) {
        $payload['venta_al_credito'][] = [
          'cuota' => $installment->cuota,
          'fecha_de_pago' => $installment->fecha_de_pago->format('d-m-Y'),
          'importe' => $installment->importe,
        ];
      }
    }

    // Contingencia
    if ($document->generado_por_contingencia) {
      $payload['generado_por_contingencia'] = true;
    }

    return $payload;
  }

  /**
   * Obtiene el endpoint según el tipo de documento
   *
   * @param string $documentTypeCode Código del tipo de documento
   * @return string El endpoint
   */
  protected function getEndpointForDocumentType(string $documentTypeCode): string
  {
    // Según documentación de Nubefact, todos los comprobantes usan el mismo endpoint
    // El tipo de documento se especifica en el payload con 'operacion' y 'tipo_de_comprobante'
    return match ($documentTypeCode) {
      '1' => '', // Factura - endpoint vacío usa la URL base
      '2' => '', // Boleta - endpoint vacío usa la URL base
      '3' => '', // Nota de Crédito - endpoint vacío usa la URL base
      '4' => '', // Nota de Débito - endpoint vacío usa la URL base
      default => '',
    };
  }

  /**
   * Obtiene el endpoint de consulta según el tipo de documento
   *
   * @param string $documentTypeCode Código del tipo de documento
   * @return string El endpoint de consulta
   */
  protected function getQueryEndpointForDocumentType(string $documentTypeCode): string
  {
    return match ($documentTypeCode) {
      '1' => 'factura/consultar',
      '2' => 'boleta/consultar',
      '3' => 'nota_credito/consultar',
      '4' => 'nota_debito/consultar',
      default => 'comprobante/consultar',
    };
  }

  /**
   * Registra la petición en la tabla de logs
   *
   * @param array $logData Datos del log
   * @return void
   */
  protected function logRequest(array $logData): void
  {
    try {
      DB::table('ap_billing_nubefact_logs')->insert([
        'ap_billing_electronic_document_id' => $logData['ap_billing_electronic_document_id'] ?? null,
        'operation' => $logData['operation'],
        'request_payload' => $logData['request_payload'],
        'response_payload' => $logData['response_payload'] ?? null,
        'http_status_code' => $logData['http_status_code'] ?? null,
        'success' => $logData['success'],
        'error_message' => $logData['error_message'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    } catch (Exception $e) {
      Log::error('Error al guardar log de Nubefact', [
        'error' => $e->getMessage(),
        'log_data' => $logData,
      ]);
    }
  }
}
