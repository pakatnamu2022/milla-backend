<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NubefactShippingGuideApiService
{
  protected string $apiUrl;
  protected string $token;
  protected string $ruc;

  public function __construct()
  {
    $this->apiUrl = config('nubefact.api_url');
    $this->token = config('nubefact.token');
    $this->ruc = config('nubefact.ruc');
  }

  /**
   * Genera y envía una guía de remisión a SUNAT mediante Nubefact
   *
   * @param object $guide La guía de remisión (ShippingGuides)
   * @return array La respuesta de Nubefact
   * @throws Exception
   */
  public function generateGuide($guide): array
  {
    $payload = $this->buildGuidePayload($guide);

    $logData = [
      'shipping_guide_id' => $guide->id,
      'operation' => 'generar_guia',
      'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ];

    try {
      $response = Http::withHeaders([
        'Authorization' => 'Token token="' . $this->token . '"',
        'Content-Type' => 'application/json',
      ])->timeout(60)->post($this->apiUrl, $payload);

      $responseData = $response->json();
      $httpStatusCode = $response->status();

      $logData['response_payload'] = json_encode($responseData, JSON_UNESCAPED_UNICODE);
      $logData['http_status_code'] = $httpStatusCode;

      if ($response->successful() && isset($responseData['serie'])) {
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

      Log::error('Error en NubefactShippingGuideApiService::generateGuide', [
        'guide_id' => $guide->id,
        'error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }

  /**
   * Consulta el estado de una guía de remisión en Nubefact
   *
   * @param object $guide La guía de remisión
   * @return array La respuesta de Nubefact
   * @throws Exception
   */
  public function queryGuide($guide): array
  {
    // Si es guía de remisión, DEBE tener type_voucher_id válido
    if ($guide->document_type == 'GUIA_REMISION') {
      if (!$guide->type_voucher_id) {
        throw new Exception('La guía de remisión debe tener un type_voucher_id válido');
      }
    }

    $tipoComprobante = $guide->typeVoucher->code_nubefact;

    $payload = [
      'operacion' => 'consultar_guia',
      'tipo_de_comprobante' => $tipoComprobante, // 7 o 8
      'serie' => $guide->series,
      'numero' => $guide->correlative,
    ];

    $logData = [
      'shipping_guide_id' => $guide->id,
      'operation' => 'consultar_guia',
      'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ];

    try {
      $response = Http::withHeaders([
        'Authorization' => 'Token token="' . $this->token . '"',
        'Content-Type' => 'application/json',
      ])->timeout(60)->post($this->apiUrl, $payload);

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

      Log::error('Error en NubefactShippingGuideApiService::queryGuide', [
        'guide_id' => $guide->id,
        'error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }

  /**
   * Construye el payload JSON para enviar a Nubefact según la documentación
   *
   * @param object $guide La guía de remisión
   * @return array El payload formateado
   */
  public function buildGuidePayload($guide): array
  {
    $tipoComprobante = $guide->typeVoucher->code_nubefact;

    if ($tipoComprobante != 7) {
      throw new Exception('El type_voucher_id de la guía de remisión no tiene code_nubefact configurado');
    }

    $payload = [
      'operacion' => 'generar_guia',
      'tipo_de_comprobante' => $tipoComprobante, // 7 = Remitente, 8 = Transportista
      'serie' => $guide->series,
      'numero' => $guide->correlative,
      'fecha_de_emision' => $guide->created_at->format('d-m-Y'),
      'peso_bruto_total' => number_format($guide->total_weight, 2, '.', ''),
      'peso_bruto_unidad_de_medida' => 'KGM', // Kilogramos
      'fecha_de_inicio_de_traslado' => $guide->issue_date->format('d-m-Y'),
      'enviar_automaticamente_al_cliente' => 'false',
      'formato_de_pdf' => '',
    ];

    // Observaciones
    if ($guide->notes) {
      $payload['observaciones'] = $guide->notes;
    }

    // Campos específicos para GRE Remitente (tipo 7)
    if ($tipoComprobante == 7) {
      $this->addRemitenteFields($guide, $payload);
    }

    // Campos específicos para GRE Transportista (tipo 8)
    //    if ($tipoComprobante == 8) {
    //      $this->addTransportistaFields($guide, $payload);
    //    }

    // Datos del conductor
    if ($guide->driver_doc) {
      $payload['conductor_documento_tipo'] = $this->getDocumentType($guide->driver_doc);
      $payload['conductor_documento_numero'] = $guide->driver_doc;

      if ($guide->driver_name) {
        $nameParts = explode(' ', trim($guide->driver_name), 2);
        $payload['conductor_nombre'] = $nameParts[0];
        $payload['conductor_apellidos'] = $nameParts[1] ?? $nameParts[0];
      }

      if ($guide->license) {
        $payload['conductor_numero_licencia'] = $guide->license;
      }
    }

    // Placa del vehículo
    if ($guide->plate) {
      $payload['transportista_placa_numero'] = $guide->plate;
    }

    // Puntos de partida y llegada
    $this->addLocationFields($guide, $payload);

    // Items (productos transportados)
    $payload['items'] = $this->buildItems($guide);

    // Documentos relacionados
    if ($guide->vehicleMovement && $guide->vehicleMovement->relatedDocuments) {
      $payload['documento_relacionado'] = $this->buildRelatedDocuments($guide->vehicleMovement);
    }

    return $payload;
  }

  /**
   * Agrega campos específicos para GRE Remitente
   */
  protected function addRemitenteFields($guide, array &$payload): void
  {
    if ($guide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA) {
      $client = $this->getClientForSale($guide);
    } else {
      $client = $this->getClientForTransfer($guide);
    }

    if ($client) {
      $this->addClientToPayload($client, $payload);
    }

    // Motivo de traslado
    if ($guide->transfer_reason_id) {
      $payload['motivo_de_traslado'] = str_pad($guide->transferReason->code_nubefact ?? '01', 2, '0', STR_PAD_LEFT);
    }

    // Número de bultos
    if ($guide->total_packages) {
      $payload['numero_de_bultos'] = $guide->total_packages;
    }

    // Tipo de transporte
    if ($guide->transfer_modality_id) {
      $modalityCode = $guide->transferModality->code_nubefact ?? '01';
      $payload['tipo_de_transporte'] = str_pad($modalityCode, 2, '0', STR_PAD_LEFT);
    }

    // Datos del transportista (si es transporte público)
    if ($guide->transfer_modality_id === SunatConcepts::TYPE_TRANSPORTATION_PUBLICO) {
      $transportCompany = $guide->transportCompany;

      if (!$transportCompany) {
        $carrierDocumentNumber = $guide->ruc_transport ?? '';
        $carrierName = $guide->company_name_transport ?? '';
      } else {
        $carrierDocumentNumber = $transportCompany->num_doc ?? '';
        $carrierName = $transportCompany->full_name ?? '';
      }

      $payload['transportista_documento_tipo'] = SunatConcepts::find(SunatConcepts::TYPE_DOCUMENT_RUC)->code_nubefact ?? '6';
      $payload['transportista_documento_numero'] = $carrierDocumentNumber;
      $payload['transportista_denominacion'] = $carrierName;
    }
  }

  /**
   * Obtiene el cliente para operación de venta
   */
  private function getClientForSale($guide): ?BusinessPartners
  {
    $vehicleDelivery = ApVehicleDelivery::where('shipping_guide_id', $guide->id)->first();
    if (!$vehicleDelivery) {
      throw new Exception('No se encontró la entrega de vehículo asociada a la guía de remisión');
    }

    if (!$vehicleDelivery->client_id) {
      return null;
    }

    $client = $vehicleDelivery->client;
    if (!$client) {
      throw new Exception('El cliente comprador no existe en la base de datos');
    }

    return $client;
  }

  /**
   * Obtiene el cliente para operación de traslado
   */
  private function getClientForTransfer($guide): ?BusinessPartners
  {
    if (!$guide->receiver_id) {
      return null;
    }

    $client = BusinessPartners::find($guide->receiver->business_partner_id);
    if (!$client) {
      throw new Exception('El cliente remitente no existe en la base de datos');
    }

    return $client;
  }

  /**
   * Agrega datos del cliente al payload
   */
  private function addClientToPayload($client, array &$payload): void
  {
    $documentTypeId = $client->documentType->id ?? null;
    $codeNubefact = SunatConcepts::where('tribute_code', $documentTypeId)
      ->where('type', 'TYPE_DOCUMENT')
      ->value('code_nubefact');
    $payload['cliente_tipo_de_documento'] = $codeNubefact;
    $payload['cliente_numero_de_documento'] = $client->num_doc ?? '';
    $payload['cliente_denominacion'] = $client->full_name ?? '';
    $payload['cliente_direccion'] = $client->direction ?? '';
    $payload['cliente_email'] = $client->email ?? '';
  }

  /**
   * Agrega campos específicos para GRE Transportista
   */
  protected function addTransportistaFields($guide, array &$payload): void
  {
    // TODO: Implementar si es necesario
  }

  /**
   * Agrega campos de ubicación (partida y llegada)
   */
  protected function addLocationFields($guide, array &$payload): void
  {
    // Punto de partida
    if ($guide->sede_transmitter_id) {
      $payload['punto_de_partida_ubigeo'] = $guide->origin_ubigeo ?? '150101';
      $payload['punto_de_partida_direccion'] = $guide->origin_address ?? '';
      $payload['punto_de_partida_codigo_establecimiento_sunat'] = '0000';
    }

    // Punto de llegada
    if ($guide->sede_receiver_id) {
      $payload['punto_de_llegada_ubigeo'] = $guide->destination_ubigeo ?? '150101';
      $payload['punto_de_llegada_direccion'] = $guide->destination_address ?? '';
      $payload['punto_de_llegada_codigo_establecimiento_sunat'] = '0000';
    }
  }

  /**
   * Construye los items (productos) de la guía
   * Maneja tanto vehículos como productos de inventario
   */
  protected function buildItems($guide): array
  {
    // Caso 1: Guía con vehículo
    if ($guide->vehicleMovement && $guide->vehicleMovement->vehicle) {
      return $this->buildVehicleItems($guide->vehicleMovement->vehicle);
    }

    // Caso 2: Guía con productos de inventario (transferencias)
    $inventoryMovement = InventoryMovement::where('reference_type', ShippingGuides::class)
      ->where('reference_id', $guide->id)
      ->with('details.product')
      ->first();

    if ($inventoryMovement && $inventoryMovement->details->isNotEmpty()) {
      // Check if it's a SERVICE type movement
      if ($inventoryMovement->item_type === 'SERVICIO') {
        return $this->buildServiceItems($inventoryMovement->details);
      }

      // Otherwise, build normal product items
      return $this->buildProductItems($inventoryMovement->details);
    }

    // Caso 3: Si no hay items, agregar uno genérico
    return [[
      'unidad_de_medida' => 'NIU',
      'codigo' => '001',
      'descripcion' => 'MERCADERIA DIVERSA',
      'cantidad' => '1',
    ]];
  }

  /**
   * Construye items para vehículos
   */
  protected function buildVehicleItems($vehicle): array
  {
    return [[
      'unidad_de_medida' => 'NIU',
      'codigo' => '001',
      'descripcion' => strtoupper(
        $vehicle->model->family->brand->name . ' ' .
          $vehicle->model->version . ' ' .
          $vehicle->model->model_year . ' ' .
          'SERIE: ' . $vehicle->vin . ' ' .
          'MOTOR: ' . $vehicle->engine_number
      ),
      'cantidad' => '1',
    ]];
  }

  /**
   * Construye items para productos de inventario
   */
  protected function buildProductItems($details): array
  {
    $items = [];

    foreach ($details as $detail) {
      $product = $detail->product;

      if (!$product) {
        continue;
      }

      $items[] = [
        'unidad_de_medida' => $product->unitMeasurement->code_nubefact ?? 'NIU',
        'codigo' => $product->code ?? 'PROD',
        'descripcion' => strtoupper($product->name ?? 'PRODUCTO'),
        'cantidad' => (string) $detail->quantity,
      ];
    }

    return $items;
  }

  /**
   * Construye items para servicios (descripciones sin productos)
   */
  protected function buildServiceItems($details): array
  {
    $items = [];

    foreach ($details as $detail) {
      // For services, use notes as description
      $description = $detail->notes ?? 'SERVICIO';

      $items[] = [
        'unidad_de_medida' => 'NIU',
        'codigo' => '001',
        'descripcion' => strtoupper($description),
        'cantidad' => (string) $detail->quantity,
      ];
    }

    return $items;
  }

  /**
   * Construye los documentos relacionados
   */
  protected function buildRelatedDocuments($vehicleMovement): array
  {
    $documents = [];

    if ($vehicleMovement->relatedDocuments) {
      foreach ($vehicleMovement->relatedDocuments as $doc) {
        $documents[] = [
          'tipo' => str_pad($doc->document_type ?? '01', 2, '0', STR_PAD_LEFT),
          'serie' => $doc->series ?? 'F001',
          'numero' => $doc->number ?? '1',
        ];
      }
    }

    return $documents;
  }

  /**
   * Determina el tipo de documento según el número
   */
  protected function getDocumentType(string $documentNumber): string
  {
    $length = strlen($documentNumber);

    if ($length == 11) {
      return '6'; // RUC
    } elseif ($length == 8) {
      return '1'; // DNI
    } elseif ($length == 12) {
      return '4'; // Carnet de extranjería
    }

    return '1'; // Por defecto DNI
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
      DB::table('nubefact_shipping_guide_logs')->insert([
        'shipping_guide_id' => $logData['shipping_guide_id'] ?? null,
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
      Log::error('Error al guardar log de Nubefact para guías', [
        'error' => $e->getMessage(),
        'log_data' => $logData,
      ]);
    }
  }
}
