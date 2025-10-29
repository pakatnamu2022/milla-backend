<?php

namespace App\Http\Services\ap\comercial;

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
        $payload = [
            'operacion' => 'consultar_guia',
            'tipo_de_comprobante' => $guide->document_type, // 7 o 8
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
        $payload = [
            'operacion' => 'generar_guia',
            'tipo_de_comprobante' => $guide->document_type, // 7 = Remitente, 8 = Transportista
            'serie' => $guide->series,
            'numero' => $guide->correlative,
            'fecha_de_emision' => $guide->issue_date->format('d-m-Y'),
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
        if ($guide->document_type == 7) {
            $this->addRemitenteFields($guide, $payload);
        }

        // Campos específicos para GRE Transportista (tipo 8)
        if ($guide->document_type == 8) {
            $this->addTransportistaFields($guide, $payload);
        }

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
        // Destinatario (cliente)
        if ($guide->receiver_id) {
            $receiver = $guide->receiver;
            $payload['cliente_tipo_de_documento'] = $this->getBusinessPartnerDocumentType($receiver);
            $payload['cliente_numero_de_documento'] = $receiver->businessPartner->document_number ?? '';
            $payload['cliente_denominacion'] = $receiver->businessPartner->business_name ?? '';
            $payload['cliente_direccion'] = $receiver->address ?? 'LIMA';
            $payload['cliente_email'] = $receiver->businessPartner->email ?? '';
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
        if ($guide->transport_company_id && $payload['tipo_de_transporte'] == '01') {
            $transportCompany = $guide->transportCompany;
            $payload['transportista_documento_tipo'] = '6'; // RUC
            $payload['transportista_documento_numero'] = $transportCompany->document_number ?? '';
            $payload['transportista_denominacion'] = $transportCompany->business_name ?? '';
        }
    }

    /**
     * Agrega campos específicos para GRE Transportista
     */
    protected function addTransportistaFields($guide, array &$payload): void
    {
        // Destinatario
        if ($guide->receiver_id) {
            $receiver = $guide->receiver;
            $payload['destinatario_documento_tipo'] = $this->getBusinessPartnerDocumentType($receiver);
            $payload['destinatario_documento_numero'] = $receiver->businessPartner->document_number ?? '';
            $payload['destinatario_denominacion'] = $receiver->businessPartner->business_name ?? '';
        }

        // Remitente (cliente)
        if ($guide->transmitter_id) {
            $transmitter = $guide->transmitter;
            $payload['cliente_tipo_de_documento'] = $this->getBusinessPartnerDocumentType($transmitter);
            $payload['cliente_numero_de_documento'] = $transmitter->businessPartner->document_number ?? '';
            $payload['cliente_denominacion'] = $transmitter->businessPartner->business_name ?? '';
            $payload['cliente_direccion'] = $transmitter->address ?? 'LIMA';
            $payload['cliente_email'] = $transmitter->businessPartner->email ?? '';
        }
    }

    /**
     * Agrega campos de ubicación (partida y llegada)
     */
    protected function addLocationFields($guide, array &$payload): void
    {
        // Punto de partida
        if ($guide->sede_transmitter_id) {
            $sedeTransmitter = $guide->sedeTransmitter;
            $payload['punto_de_partida_ubigeo'] = $sedeTransmitter->ubigeo ?? '150101';
            $payload['punto_de_partida_direccion'] = $sedeTransmitter->address ?? '';
            $payload['punto_de_partida_codigo_establecimiento_sunat'] = $sedeTransmitter->code_sunat ?? '0000';
        }

        // Punto de llegada
        if ($guide->sede_receiver_id) {
            $sedeReceiver = $guide->sedeReceiver;
            $payload['punto_de_llegada_ubigeo'] = $sedeReceiver->ubigeo ?? '150101';
            $payload['punto_de_llegada_direccion'] = $sedeReceiver->address ?? '';
            $payload['punto_de_llegada_codigo_establecimiento_sunat'] = $sedeReceiver->code_sunat ?? '0000';
        }
    }

    /**
     * Construye los items (productos) de la guía
     */
    protected function buildItems($guide): array
    {
        $items = [];

        // Si la guía tiene relación con movimiento de vehículo, obtener los items de ahí
        if ($guide->vehicleMovement && $guide->vehicleMovement->items) {
            foreach ($guide->vehicleMovement->items as $item) {
                $items[] = [
                    'unidad_de_medida' => $item->unit ?? 'NIU',
                    'codigo' => $item->product_code ?? '001',
                    'descripcion' => $item->description ?? 'PRODUCTO',
                    'cantidad' => number_format($item->quantity, 2, '.', ''),
                ];
            }
        }

        // Si no hay items, agregar uno genérico
        if (empty($items)) {
            $items[] = [
                'unidad_de_medida' => 'NIU',
                'codigo' => '001',
                'descripcion' => 'MERCADERIA DIVERSA',
                'cantidad' => '1',
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
     * Obtiene el tipo de documento de un socio de negocios
     */
    protected function getBusinessPartnerDocumentType($establishment): string
    {
        if (!$establishment || !$establishment->businessPartner) {
            return '6'; // RUC por defecto
        }

        $documentNumber = $establishment->businessPartner->document_number ?? '';
        return $this->getDocumentType($documentNumber);
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
