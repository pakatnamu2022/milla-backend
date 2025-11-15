<?php

namespace App\Http\Services\ap\facturacion;

use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Resources\Dynamics\SalesDocumentDetailDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentSerialDynamicsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Jobs\SyncSalesDocumentJob;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NumberFormatter;
use Throwable;

class ElectronicDocumentService extends BaseService implements BaseServiceInterface
{
  protected NubefactApiService $nubefactService;

  public function __construct(NubefactApiService $nubefactService)
  {
    $this->nubefactService = $nubefactService;
  }

  /**
   * List all electronic documents with filtering, sorting, and pagination
   */
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      ElectronicDocument::class,
      $request,
      ElectronicDocument::filters,
      ElectronicDocument::sorts,
      ElectronicDocumentResource::class,
      ['documentType', 'currency', 'identityDocumentType', 'items', 'creator']
    );
  }

  /**
   * Find a specific electronic document by ID
   * @throws Exception
   */
  public function find($id): ElectronicDocument
  {
    $document = ElectronicDocument::with([
      'documentType',
      'transactionType',
      'identityDocumentType',
      'currency',
      'items.igvType',
      'guides',
      'installments',
      'vehicleMovement',
      'creator',
      'updater'
    ])->find($id);

    if (!$document) {
      throw new Exception('Documento electrónico no encontrado');
    }

    return $document;
  }

  public function show(int $id)
  {
    $electronicDocument = ElectronicDocument::find($id);
    return new ElectronicDocumentResource($electronicDocument);
  }

  /**
   * Get the next document number for a given type and series
   * @param string $documentType
   * @param string $series
   * @return int
   * @throws Exception
   */
  public function nextDocumentNumber(string $documentType, string $series): array
  {
    $query = ElectronicDocument::where('sunat_concept_document_type_id', $documentType)
      ->where('serie', $series)
      ->whereNull('deleted_at');

    /**
     * TODO: Delete this block and always use nextCorrelativeQuery
     */
    if ($query->count() == 0) {
      if (($documentType == ElectronicDocument::TYPE_FACTURA)) {
        $startCorrelative = 26;
        $correlative = (int)$this->nextCorrelativeQuery($query, 'numero') + $startCorrelative;
      } else if ($documentType == ElectronicDocument::TYPE_BOLETA) {
        $startCorrelativeNC = 1;
        $correlative = (int)$this->nextCorrelativeQuery($query, 'numero') + $startCorrelativeNC;
      } else if ($documentType == ElectronicDocument::TYPE_NOTA_CREDITO) {
        $startCorrelativeNC = 2;
        $correlative = (int)$this->nextCorrelativeQuery($query, 'numero') + $startCorrelativeNC;
      } else if ($documentType == ElectronicDocument::TYPE_NOTA_DEBITO) {
        $startCorrelativeND = 1;
        $correlative = (int)$this->nextCorrelativeQuery($query, 'numero') + $startCorrelativeND;
      } else {
        throw new Exception('El tipo de documento no es valido');
      }
    } else {
      $correlative = (int)$this->nextCorrelativeQuery($query, 'numero');
    }

    $number = $this->completeNumber($correlative);
    return ["number" => $number];
  }

  /**
   * @param string $documentType
   * @param string $series
   * @return array[]|int[]
   */
  private function nextDocumentNumberCorrelative(string $documentType, string $series): array
  {
    $number = $this->nextDocumentNumber($documentType, $series);
    return ["number" => (int)$number["number"]];
  }

  /**
   * Create a new electronic document
   * @throws Exception
   * @throws Throwable
   */
  public function store(mixed $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      /**
       * Validar y calcular el siguiente número correlativo si no se proporciona
       */
      $nextNumberData = $this->nextDocumentNumberCorrelative(
        $data['sunat_concept_document_type_id'],
        $data['serie']
      );
      $data['numero'] = $nextNumberData['number'];

      /**
       * Validar que la serie sea correcta
       */
      if (!ElectronicDocument::validateSerie($data['sunat_concept_document_type_id'], $data['serie'])) {
        throw new Exception('La serie no es válida para el tipo de documento seleccionado');
      }

      /**
       * Obtener la tasa de cambio actual si la moneda es USD
       */
      $exchangeRate = (new ExchangeRateService())->getCurrentUSDRate();

      $client = BusinessPartners::find($data['client_id']);
      $documentType = SunatConcepts::where('tribute_code', $client->document_type_id)
        ->where('type', SunatConcepts::TYPE_DOCUMENT)
        ->first();

      $data['sunat_concept_identity_document_type_id'] = $documentType->id;
      $data['cliente_numero_de_documento'] = $client->num_doc;
      $data['cliente_denominacion'] = $client->full_name . ($client->spouse_full_name ? ' - ' . $client->spouse_full_name : '');
      $data['cliente_direccion'] = $client->direction;
      $data['cliente_email'] = $client->email;
      $data['porcentaje_de_igv'] = $client->taxClassType->igv;
      $data['client_id'] = $client->id;
      $data['tipo_de_cambio'] = $exchangeRate->rate;


      /**
       * Crear el documento principal
       */
      $document = ElectronicDocument::create(array_merge($data, [
        'exchange_rate_id' => $exchangeRate->id,
        'created_by' => auth()->id(),
        'status' => ElectronicDocument::STATUS_DRAFT,
      ]));

      // Crear los items
      if (isset($data['items']) && is_array($data['items'])) {
        $data['items'] = collect($data['items'])->sortBy('anticipo_regularizacion')->values()->all();
        foreach ($data['items'] as $index => $itemData) {
          $itemData['line_number'] = $index + 1;
          $document->items()->create($itemData);
        }
      }

      // Crear guías de remisión si existen
      if (isset($data['guias']) && is_array($data['guias'])) {
        foreach ($data['guias'] as $guiaData) {
          $document->guides()->create($guiaData);
        }
      }

      // Crear cuotas si es venta al crédito
      if (isset($data['venta_al_credito']) && is_array($data['venta_al_credito'])) {
        foreach ($data['venta_al_credito'] as $cuotaData) {
          $document->installments()->create($cuotaData);
        }
      }

      // Crear movimiento de vehículo si viene ap_vehicle_id
      if (isset($data['ap_vehicle_id']) && $data['ap_vehicle_id']) {
        $vehicleMovement = $this->createVehicleMovement($data['ap_vehicle_id'], $document);

        // Actualizar el documento con el ID del movimiento
        $document->update([
          'ap_vehicle_movement_id' => $vehicleMovement->id
        ]);
      }

      DB::commit();
      return new ElectronicDocumentResource($document->load(['items', 'guides', 'installments', 'vehicleMovement']));
    } catch (Throwable $e) {
      DB::rollBack();
      Log::error('Error creating electronic document', [
        'error' => $e->getMessage(),
        'data' => $data
      ]);
      throw new Exception('Error al crear el documento electrónico: ' . $e->getMessage());
    }
  }

  /**
   * Update an electronic document
   * @throws Exception
   */
  public function update(mixed $data): ElectronicDocumentResource
  {
    $id = $data['id'];
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Solo se pueden actualizar documentos en estado draft
      if ($document->status !== ElectronicDocument::STATUS_DRAFT) {
        throw new Exception('Solo se pueden actualizar documentos en estado borrador');
      }

      // Prevenir cambio de número correlativo
      if (isset($data['numero']) && $data['numero'] !== $document->numero) {
        throw new Exception('No se puede cambiar el número correlativo del documento');
      }

      // Validar serie si está siendo actualizada
      if (isset($data['serie']) && isset($data['sunat_concept_document_type_id'])) {
        if (!ElectronicDocument::validateSerie($data['sunat_concept_document_type_id'], $data['serie'])) {
          throw new Exception('La serie no es válida para el tipo de documento seleccionado');
        }
      }

      // Actualizar datos del cliente si el client_id está cambiando
      if (isset($data['client_id']) && $data['client_id'] !== $document->client_id) {
        $client = BusinessPartners::find($data['client_id']);
        if (!$client) {
          throw new Exception('Cliente no encontrado');
        }

        $documentType = SunatConcepts::where('tribute_code', $client->document_type_id)
          ->where('type', SunatConcepts::TYPE_DOCUMENT)
          ->first();

        $data['sunat_concept_identity_document_type_id'] = $documentType->id;
        $data['cliente_numero_de_documento'] = $client->num_doc;
        $data['cliente_denominacion'] = $client->full_name . ($client->spouse_full_name ? ' - ' . $client->spouse_full_name : '');
        $data['cliente_direccion'] = $client->direction;
        $data['cliente_email'] = $client->email;
        $data['porcentaje_de_igv'] = $client->taxClassType->igv;
      }

      // Actualizar tipo de cambio si la moneda está cambiando
      if (isset($data['sunat_concept_currency_id']) && $data['sunat_concept_currency_id'] !== $document->sunat_concept_currency_id) {
        $exchangeRate = (new ExchangeRateService())->getCurrentUSDRate();
        $data['tipo_de_cambio'] = $exchangeRate->rate;
        $data['exchange_rate_id'] = $exchangeRate->id;
      }

      // Actualizar el documento
      $document->update(array_merge($data, [
        'updated_by' => auth()->id(),
      ]));

      // Actualizar items si se proporcionan
      if (isset($data['items']) && is_array($data['items'])) {
        // Eliminar items existentes
        $document->items()->delete();

        // Crear nuevos items
        foreach ($data['items'] as $index => $itemData) {
          $itemData['line_number'] = $index + 1;
          $document->items()->create($itemData);
        }
      }

      // Actualizar guías si se proporcionan
      if (isset($data['guias']) && is_array($data['guias'])) {
        $document->guides()->delete();
        foreach ($data['guias'] as $guiaData) {
          $document->guides()->create($guiaData);
        }
      }

      // Actualizar cuotas si se proporcionan
      if (isset($data['venta_al_credito']) && is_array($data['venta_al_credito'])) {
        $document->installments()->delete();
        foreach ($data['venta_al_credito'] as $cuotaData) {
          $document->installments()->create($cuotaData);
        }
      }

      DB::commit();
      return new ElectronicDocumentResource($document->fresh(['items', 'guides', 'installments']));
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error updating electronic document', [
        'id' => $id,
        'error' => $e->getMessage(),
        'data' => $data
      ]);
      throw new Exception('Error al actualizar el documento electrónico: ' . $e->getMessage());
    }
  }

  /**
   * Delete an electronic document
   * @throws Exception
   */
  public function destroy($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Solo se pueden eliminar documentos en estado draft o que no hayan sido aceptados por SUNAT
      if ($document->status === ElectronicDocument::STATUS_ACCEPTED && $document->aceptada_por_sunat) {
        throw new Exception('No se puede eliminar un documento aceptado por SUNAT. Debe anularlo primero.');
      }

      $document->delete();

      DB::commit();
      return response()->json([
        'success' => true,
        'message' => 'Documento electrónico eliminado correctamente'
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error deleting electronic document', [
        'id' => $id,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al eliminar el documento electrónico: ' . $e->getMessage());
    }
  }

  /**
   * Send document to Nubefact API
   * @throws Exception
   */
  public function sendToNubefact($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Validar que el documento esté en estado correcto
      if ($document->status === ElectronicDocument::STATUS_ACCEPTED) {
        throw new Exception('El documento ya ha sido aceptado por SUNAT');
      }

      if ($document->anulado) {
        throw new Exception('No se puede enviar un documento anulado');
      }

      // Marcar como enviado
      $document->markAsSent();

      // Enviar a Nubefact
      $response = $this->nubefactService->generateDocument($document);

      // Validar estructura de respuesta
      if (!is_array($response)) {
        Log::error('Respuesta de Nubefact no es un array', ['response' => $response]);
        throw new Exception('Respuesta inválida de Nubefact: formato inesperado');
      }

      // El servicio devuelve ['success' => bool, 'data' => array] o ['success' => bool, 'error' => string, 'data' => array]
      if (!$response['success']) {
        $errorMessage = is_array($response['error']) ? implode(', ', $response['error']) : ($response['error'] ?? 'Error desconocido');
        throw new Exception('Error de Nubefact: ' . $errorMessage);
      }

      // Obtener los datos reales de Nubefact
      $nubefactData = $response['data'] ?? [];

      if (!isset($nubefactData['aceptada_por_sunat'])) {
        Log::error('Respuesta de Nubefact sin clave aceptada_por_sunat', ['response' => $nubefactData]);
        throw new Exception('Respuesta inválida de Nubefact: falta campo aceptada_por_sunat');
      }

      // Procesar respuesta
      if (isset($nubefactData['enlace_del_pdf']) && isset($nubefactData['enlace_del_xml']) && !isset($nubefactData['enlace_del_cdr'])) {
        $document->update([
          'enlace_del_pdf' => $nubefactData['enlace_del_pdf'],
          'enlace_del_xml' => $nubefactData['enlace_del_xml'],
        ]);
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Documento procesado correctamente',
        'data' => new ElectronicDocumentResource($document->fresh()),
        'sunat_response' => $nubefactData
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error sending document to Nubefact', [
        'id' => $id,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al enviar el documento a Nubefact: ' . $e->getMessage());
    }
  }

  /**
   * Query document status from Nubefact
   * @throws Exception
   */
  public function queryFromNubefact($id): JsonResponse
  {
    try {
      $document = $this->find($id);

      $response = $this->nubefactService->queryDocument($document);

      // Extraer los datos de la respuesta
      $nubefactData = $response['data'] ?? $response;

      DB::beginTransaction();

      // Si el documento fue aceptado por SUNAT, actualizar usando el metodo del modelo
      if (isset($nubefactData['aceptada_por_sunat']) && $nubefactData['aceptada_por_sunat'] && !$document->aceptada_por_sunat) {
        $document->markAsAccepted($nubefactData);
        SyncSalesDocumentJob::dispatch($id);
        $document->markAsInProgress();
      }

      // Verificar si el documento fue anulado en Nubefact
      if (isset($nubefactData['anulado']) && $nubefactData['anulado'] === true) {
        // Si el documento no está marcado como cancelado en nuestra BD, actualizarlo
        if ($document->status !== ElectronicDocument::STATUS_CANCELLED || !$document->anulado) {
          $document->markAsCancelled();
        }
      }

      if ($nubefactData['aceptada_por_sunat'] !== $document->aceptada_por_sunat) {
        $document->aceptada_por_sunat = $nubefactData['aceptada_por_sunat'];
        $document->save();
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Estado consultado correctamente',
        'data' => new ElectronicDocumentResource($document->fresh()),
        'sunat_response' => $nubefactData
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error querying document from Nubefact', [
        'id' => $id,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al consultar el documento en Nubefact: ' . $e->getMessage());
    }
  }

  /**
   * Cancel document in Nubefact (Comunicación de baja)
   * @throws Exception
   */
  public function cancelInNubefact($id, string $reason): JsonResponse
  {
    DB::beginTransaction();
    try {
      $document = $this->find($id);

      // Validar que el documento esté aceptado
      if (!$document->aceptada_por_sunat) {
        throw new Exception('Solo se pueden anular documentos aceptados por SUNAT');
      }

      if ($document->anulado) {
        throw new Exception('El documento ya está anulado');
      }

      $electronicDocumentItem = ElectronicDocumentItem::where('anticipo_documento_serie', $document->serie)
        ->where('anticipo_documento_numero', $document->numero)
        ->whereNull('deleted_at');

      if ($electronicDocumentItem->count() > 0) {
        throw new Exception('El documento no se puede anular porque tiene anticipos asociados');
      }

      // Enviar anulación a Nubefact
      $response = $this->nubefactService->cancelDocument($document, $reason);

      // Marcar como cancelado
      $document->markAsLocalCancelled($reason);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Documento anulado correctamente en SUNAT',
        'data' => new ElectronicDocumentResource($document->fresh()),
        'sunat_response' => $response
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error cancelling document in Nubefact', [
        'id' => $id,
        'reason' => $reason,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al anular el documento: ' . $e->getMessage());
    }
  }

  /**
   * Calculate totals from items array
   * @param array $items
   * @return array
   */
  private function calculateTotalsFromItemsNotes(array $items): array
  {
    $totals = [
      'total_gravada' => 0,
      'total_inafecta' => 0,
      'total_exonerada' => 0,
      'total_igv' => 0,
      'total_gratuita' => 0,
      'total_descuento' => 0,
      'total_anticipo' => 0,
      'total_otros_cargos' => 0,
      'total_isc' => 0,
      'total' => 0,
    ];

    foreach ($items as $item) {
      // Verificar si es un item de anticipo regularización
      $isAnticipo = isset($item['anticipo_regularizacion']) && $item['anticipo_regularizacion'] === true;

      // Determinar el multiplicador (1 para items normales, -1 para anticipos)
      $multiplier = $isAnticipo ? -1 : 1;

      // Acumular IGV (restar si es anticipo)
      $totals['total_igv'] += $multiplier * (float)($item['igv'] ?? 0);

      // Acumular descuentos
      $totals['total_descuento'] += (float)($item['descuento'] ?? 0);

      // Acumular total anticipo (siempre positivo)
      if ($isAnticipo) {
        $totals['total_anticipo'] += (float)($item['total'] ?? 0);
      }

      // Acumular total (restar si es anticipo)
      $totals['total'] += $multiplier * (float)($item['total'] ?? 0);

      // Determinar el tipo de IGV y acumular en el total correspondiente
      $igvTypeId = $item['sunat_concept_igv_type_id'] ?? null;
      $subtotal = (float)($item['subtotal'] ?? 0);

      // Buscar el código del tipo de IGV
      $igvType = SunatConcepts::find($igvTypeId);
      $igvCode = $igvType->code_nubefact ?? null;

      if ($igvCode) {
        if ($igvCode == '1') {
          // Gravado - Operación Onerosa (restar si es anticipo)
          $totals['total_gravada'] += $multiplier * $subtotal;
        } elseif ($igvCode == '20') {
          // Exonerado - Operación Onerosa (restar si es anticipo)
          $totals['total_exonerada'] += $multiplier * $subtotal;
        } elseif ($igvCode == '30') {
          // Inafecto - Operación Onerosa (restar si es anticipo)
          $totals['total_inafecta'] += $multiplier * $subtotal;
        } elseif (in_array($igvCode, ['11', '12', '13', '14', '15', '16', '17', '21', '31', '32', '33', '34', '35', '36', '37'])) {
          // Operaciones gratuitas (restar si es anticipo)
          $totals['total_gratuita'] += $multiplier * $subtotal;
        }
      }
    }

    // Redondear a 2 decimales
    foreach ($totals as $key => $value) {
      $totals[$key] = round($value, 2);
    }

    return $totals;
  }

  /**
   * Create a credit note from an existing document
   * @throws Exception
   */
  public function createCreditNote($originalDocumentId, array $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      $originalDocument = $this->find($originalDocumentId);

      // Validar que el documento original esté aceptado
      if (!$originalDocument->aceptada_por_sunat) {
        throw new Exception('Solo se pueden crear notas de crédito para documentos aceptados por SUNAT');
      }

      // Calcular totales desde items si no se proporcionan
      if (isset($data['items']) && is_array($data['items'])) {
        $calculatedTotals = $this->calculateTotalsFromItemsNotes($data['items']);

        // Log para debug
        Log::info('Totales calculados para NC', [
          'calculated' => $calculatedTotals,
          'items_count' => count($data['items']),
          'items' => $data['items']
        ]);

        // Merge calculated totals only if not provided by user
        foreach ($calculatedTotals as $key => $value) {
          if (!isset($data[$key])) {
            $data[$key] = $value;
          }
        }
      }

      // Copiar moneda y tipo de cambio del documento original
      if (!isset($data['sunat_concept_currency_id'])) {
        $data['sunat_concept_currency_id'] = $originalDocument->sunat_concept_currency_id;
      }
      if (!isset($data['tipo_de_cambio'])) {
        $data['tipo_de_cambio'] = $originalDocument->tipo_de_cambio;
      }

      // Copiar cliente del documento original
      if (!isset($data['client_id'])) {
        $data['client_id'] = $originalDocument->client_id;
      }

      // Copiar tipo de transacción del documento original
      if (!isset($data['sunat_concept_transaction_type_id'])) {
        $data['sunat_concept_transaction_type_id'] = $originalDocument->sunat_concept_transaction_type_id;
      }

      // Preparar datos de la nota de crédito
      $creditNoteData = array_merge($data, [
        'sunat_concept_document_type_id' => ElectronicDocument::TYPE_NOTA_CREDITO,
        'documento_que_se_modifica_tipo' => $originalDocument->documentType->code_nubefact,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'original_document_id' => $originalDocumentId,
        'origin_module' => $originalDocument->origin_module,
        'origin_entity_type' => $originalDocument->origin_entity_type,
        'origin_entity_id' => $originalDocument->origin_entity_id,
        'purchase_request_quote_id' => $originalDocument->purchase_request_quote_id ?? null,
      ]);

      // Crear la nota de crédito
      $creditNote = $this->store($creditNoteData);

      $originalDocument->update([
        'credit_note_id' => $creditNote->id,
      ]);

      DB::commit();
      return $creditNote;
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error creating credit note', [
        'original_document_id' => $originalDocumentId,
        'error' => $e->getMessage(),
        'data' => $data
      ]);
      throw new Exception('Error al crear la nota de crédito: ' . $e->getMessage());
    }
  }

  /**
   * Create a debit note from an existing document
   * @throws Exception
   */
  public function createDebitNote($originalDocumentId, array $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      $originalDocument = $this->find($originalDocumentId);

      // Validar que el documento original esté aceptado
      if (!$originalDocument->aceptada_por_sunat) {
        throw new Exception('Solo se pueden crear notas de débito para documentos aceptados por SUNAT');
      }

      // Calcular totales desde items si no se proporcionan
      if (isset($data['items']) && is_array($data['items'])) {
        $calculatedTotals = $this->calculateTotalsFromItemsNotes($data['items']);

        // Merge calculated totals only if not provided by user
        foreach ($calculatedTotals as $key => $value) {
          if (!isset($data[$key])) {
            $data[$key] = $value;
          }
        }
      }

      // Copiar moneda y tipo de cambio del documento original
      if (!isset($data['sunat_concept_currency_id'])) {
        $data['sunat_concept_currency_id'] = $originalDocument->sunat_concept_currency_id;
      }
      if (!isset($data['tipo_de_cambio'])) {
        $data['tipo_de_cambio'] = $originalDocument->tipo_de_cambio;
      }

      // Copiar cliente del documento original
      if (!isset($data['client_id'])) {
        $data['client_id'] = $originalDocument->client_id;
      }

      // Copiar tipo de transacción del documento original
      if (!isset($data['sunat_concept_transaction_type_id'])) {
        $data['sunat_concept_transaction_type_id'] = $originalDocument->sunat_concept_transaction_type_id;
      }

      // Preparar datos de la nota de débito
      $debitNoteData = array_merge($data, [
        'sunat_concept_document_type_id' => ElectronicDocument::TYPE_NOTA_DEBITO,
        'documento_que_se_modifica_tipo' => $originalDocument->documentType->code_nubefact,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'original_document_id' => $originalDocumentId,
        'origin_module' => $originalDocument->origin_module,
        'origin_entity_type' => $originalDocument->origin_entity_type,
        'origin_entity_id' => $originalDocument->origin_entity_id,
        'purchase_request_quote_id' => $originalDocument->purchase_request_quote_id ?? null,
      ]);

      // Validar límite razonable para notas de débito (200% del original)
      $originalTotal = (float)$originalDocument->total;
      $debitNoteTotal = (float)$debitNoteData['total'];
      $maxAllowedTotal = $originalTotal * 2;

      if ($debitNoteTotal > $maxAllowedTotal) {
        throw new Exception(sprintf(
          'El total de la nota de débito (%.2f) excede el límite permitido (%.2f). El total no puede ser mayor al 200%% del documento original (%.2f)',
          $debitNoteTotal,
          $maxAllowedTotal,
          $originalTotal
        ));
      }

      // Crear la nota de débito
      $debitNote = $this->store($debitNoteData);

      $originalDocument->update([
        'debit_note_id' => $debitNote->id,
      ]);

      DB::commit();
      return $debitNote;
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error creating debit note', [
        'original_document_id' => $originalDocumentId,
        'error' => $e->getMessage(),
        'data' => $data
      ]);
      throw new Exception('Error al crear la nota de débito: ' . $e->getMessage());
    }
  }

  /**
   * Get documents by module and entity
   */
  public function getByOriginEntity(string $module, string $entityType, int $entityId): JsonResponse
  {
    try {
      $documents = ElectronicDocument::with(['documentType', 'currency', 'items'])
        ->where('origin_module', $module)
        ->where('origin_entity_type', $entityType)
        ->where('origin_entity_id', $entityId)
        ->orderBy('fecha_de_emision', 'desc')
        ->get();

      return response()->json([
        'success' => true,
        'data' => ElectronicDocumentResource::collection($documents)
      ]);
    } catch (Exception $e) {
      Log::error('Error getting documents by origin entity', [
        'module' => $module,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al obtener documentos: ' . $e->getMessage());
    }
  }

  /**
   * Calculate totals from items
   */
  public function calculateTotalsFromItems(array $items): array
  {
    $totals = [
      'total_gravada' => 0,
      'total_exonerada' => 0,
      'total_inafecta' => 0,
      'total_gratuita' => 0,
      'total_igv' => 0,
      'total' => 0,
    ];

    foreach ($items as $item) {
      $igvTypeCode = $item['tipo_de_igv'] ?? 1;

      // Determinar el tipo de operación según el código IGV
      if ($igvTypeCode == 1) { // Gravado
        $totals['total_gravada'] += $item['subtotal'];
        $totals['total_igv'] += $item['igv'];
      } elseif ($igvTypeCode == 8) { // Exonerado
        $totals['total_exonerada'] += $item['subtotal'];
      } elseif ($igvTypeCode == 9) { // Inafecto
        $totals['total_inafecta'] += $item['subtotal'];
      } elseif ($igvTypeCode == 17 || $igvTypeCode == 20) { // Gratuito
        $totals['total_gratuita'] += $item['subtotal'];
      }
    }

    // Calcular total (las operaciones gratuitas no suman)
    $totals['total'] = $totals['total_gravada'] + $totals['total_exonerada'] + $totals['total_inafecta'] + $totals['total_igv'];

    return $totals;
  }

  /**
   * Obtiene los anticipos pendientes de regularización para una entidad origen
   */
  public function getPendingAnticipos(string $module, string $entityType, int $entityId)
  {
    return ElectronicDocument::byOriginEntity($module, $entityType, $entityId)
      ->anticipos()
      ->acceptedBySunat()
      ->notCancelled()
      ->get()
      ->filter(function ($anticipo) {
        return !$anticipo->isRegularized();
      })
      ->values();
  }

  /**
   * Calcula los totales para una factura de regularización
   */
  public function calculateRegularizationTotals(float $vehiclePrice, $anticipos): array
  {
    $totalAnticipos = $anticipos->sum('total');
    $totalGravada = ($vehiclePrice / 1.18) - ($totalAnticipos / 1.18);
    $totalIgv = $vehiclePrice - ($vehiclePrice / 1.18) - ($totalAnticipos - ($totalAnticipos / 1.18));
    $totalFinal = $vehiclePrice - $totalAnticipos;

    return [
      'total_anticipo' => round($totalAnticipos, 2),
      'total_gravada' => round($totalGravada, 2),
      'total_igv' => round($totalIgv, 2),
      'total' => round($totalFinal, 2),
    ];
  }

  /**
   * Construye los items para una factura de regularización
   */
  public function buildRegularizationItems($vehicle, $anticipos, array $additionalData = []): array
  {
    $items = [];
    $vehiclePrice = (float)$vehicle->model->sale_price;
    $porcentajeIgv = 18;

    // Item 1: Producto principal (vehículo completo) - POSITIVO
    $valorUnitario = round($vehiclePrice / (1 + ($porcentajeIgv / 100)), 2);
    $igv = round($vehiclePrice - $valorUnitario, 2);

    $items[] = [
      'unidad_de_medida' => 'NIU',
      'codigo' => $additionalData['codigo'] ?? 'VEH-001',
      'descripcion' => $additionalData['descripcion'] ?? "Vehículo {$vehicle->model->commercial_brand->name} {$vehicle->model->model} {$vehicle->model->year} - VIN: {$vehicle->vin}",
      'cantidad' => 1,
      'valor_unitario' => $valorUnitario,
      'precio_unitario' => $vehiclePrice,
      'descuento' => 0,
      'subtotal' => $valorUnitario,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_ANTICIPO_GRAVADO, // Tipo 1
      'igv' => $igv,
      'total' => $vehiclePrice,
      'anticipo_regularizacion' => false,
    ];

    // Items 2-N: Anticipos (negativos)
    foreach ($anticipos as $anticipo) {
      $anticipoTotal = (float)$anticipo->total;
      $anticipoValorUnitario = round(-($anticipoTotal / (1 + ($porcentajeIgv / 100))), 2);
      $anticipoIgv = round(-($anticipoTotal - abs($anticipoValorUnitario)), 2);

      $items[] = [
        'unidad_de_medida' => 'ZZ',
        'codigo' => "ANT-{$anticipo->serie}-{$anticipo->numero}",
        'descripcion' => "Anticipo {$anticipo->serie}-{$anticipo->numero}",
        'cantidad' => 1,
        'valor_unitario' => $anticipoValorUnitario,
        'precio_unitario' => -$anticipoTotal,
        'descuento' => 0,
        'subtotal' => $anticipoValorUnitario,
        'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_ANTICIPO_GRAVADO, // Tipo 1
        'igv' => $anticipoIgv,
        'total' => -$anticipoTotal,
        'anticipo_regularizacion' => true,
        'anticipo_documento_serie' => $anticipo->serie,
        'anticipo_documento_numero' => $anticipo->numero,
      ];
    }

    return $items;
  }

  /**
   * Generate PDF for electronic document
   * @throws Exception
   */
  public function generatePDF($id)
  {
    try {
      $document = $this->find($id);
      $resource = new ElectronicDocumentResource($document);
      $dataArray = $resource->resolve();

      // Agregar datos adicionales para el PDF
      $dataArray['currency_symbol'] = $document->currency->symbol ?? 'S/';
      $dataArray['document_type_name'] = $document->documentType->description ?? '';
      $dataArray['identity_document_type_name'] = $document->identityDocumentType->description ?? '';
      $dataArray['transaction_type_name'] = $document->transactionType->description ?? '';

      // Cargar items con sus relaciones
      $dataArray['items_collection'] = $document->items->map(function ($item) {
        return [
          'codigo' => $item->codigo,
          'descripcion' => $item->descripcion,
          'unidad_de_medida' => $item->unidad_de_medida,
          'cantidad' => $item->cantidad,
          'valor_unitario' => $item->valor_unitario,
          'precio_unitario' => $item->precio_unitario,
          'descuento' => $item->descuento,
          'subtotal' => $item->subtotal,
          'igv' => $item->igv,
          'total' => $item->total,
          'igv_type_description' => $item->igvType->description ?? '',
        ];
      })->toArray();

      // Convertir totales en letras
      $dataArray['total_en_letras'] = $this->convertNumberToWords($document->total);

      $pdf = PDF::loadView('reports.ap.facturacion.electronic-document', ['document' => $dataArray]);

      // Configurar PDF
      $pdf->setOptions([
        'defaultFont' => 'Arial',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => false,
        'dpi' => 96,
      ]);

      // Tamaño A4
      $pdf->setPaper('A4', 'portrait');

      return $pdf;
    } catch (Exception $e) {
      Log::error('Error generating PDF for electronic document', [
        'id' => $id,
        'error' => $e->getMessage()
      ]);
      throw new Exception('Error al generar el PDF: ' . $e->getMessage());
    }
  }

  /**
   * Create vehicle movement when electronic document is created
   * @param int $vehicleId
   * @param ElectronicDocument $document
   * @return \App\Models\ap\comercial\VehicleMovement
   * @throws Exception
   */
  private function createVehicleMovement(int $vehicleId, ElectronicDocument $document)
  {
    try {
      $vehicle = \App\Models\ap\comercial\Vehicles::find($vehicleId);

      if (!$vehicle) {
        throw new Exception("Vehículo con ID {$vehicleId} no encontrado");
      }

      // Obtener el estado anterior del vehículo
      $previousStatusId = $vehicle->ap_vehicle_status_id;

      // Crear el movimiento de vehículo
      $vehicleMovement = \App\Models\ap\comercial\VehicleMovement::create([
        'movement_type' => 'VENTA',
        'ap_vehicle_id' => $vehicleId,
        'ap_vehicle_status_id' => \App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus::VENDIDO_NO_ENTREGADO,
        'movement_date' => now(),
        'observation' => "Venta de vehículo - Documento: {$document->serie}-{$document->numero}",
        'previous_status_id' => $previousStatusId,
        'new_status_id' => \App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus::VENDIDO_NO_ENTREGADO,
        'created_by' => auth()->id(),
      ]);

      // Actualizar el estado del vehículo
      $vehicle->update([
        'ap_vehicle_status_id' => \App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus::VENDIDO_NO_ENTREGADO,
      ]);

      Log::info('Vehicle movement created for electronic document', [
        'vehicle_id' => $vehicleId,
        'movement_id' => $vehicleMovement->id,
        'document_id' => $document->id,
        'document_number' => "{$document->serie}-{$document->numero}",
        'previous_status' => $previousStatusId,
        'new_status' => \App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus::VENDIDO_NO_ENTREGADO,
      ]);

      return $vehicleMovement;
    } catch (Exception $e) {
      Log::error('Error creating vehicle movement for electronic document', [
        'vehicle_id' => $vehicleId,
        'document_id' => $document->id,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Convert number to words (Spanish)
   */
  private function convertNumberToWords($number): string
  {
    $formatter = new NumberFormatter('es', NumberFormatter::SPELLOUT);
    $integerPart = floor($number);
    $decimalPart = round(($number - $integerPart) * 100);

    $words = strtoupper($formatter->format($integerPart));

    return "{$words} CON {$decimalPart}/100";
  }

  /**
   * @throws Exception
   */
  public function nextCreditNoteNumber(array $data, $id): array
  {
    /**
     * TODO: Change series to series_id in the future
     */
    $series = AssignSalesSeries::find($data['series']);
    $electronicDocument = $this->find($id);
    $electronicDocumentItem = ElectronicDocumentItem::where('anticipo_documento_serie', $electronicDocument->serie)
      ->where('anticipo_documento_numero', $electronicDocument->numero)
      ->whereNull('deleted_at');

    if ($electronicDocumentItem->count() > 0) {
      throw new Exception('El anticipo ya ha sido regularizado, no se puede crear una nota de crédito. En su lugar cree una nota de crédito para el documento de regularización.');
    }

    return [
      'series' => $series->series,
      'number' => $this->nextDocumentNumber(
        ElectronicDocument::TYPE_NOTA_CREDITO,
        $series->series
      )['number']
    ];

  }

  /**
   * @throws Exception
   */
  public function nextDebitNoteNumber(array $data, $id): array
  {
    /**
     * TODO: Change series to series_id in the future
     */
    $series = AssignSalesSeries::find($data['series']);
    $electronicDocument = $this->find($id);
    $electronicDocumentItem = ElectronicDocumentItem::where('anticipo_documento_serie', $electronicDocument->serie)
      ->where('anticipo_documento_numero', $electronicDocument->numero)
      ->whereNull('deleted_at');

    if ($electronicDocumentItem->count() > 0) {
      throw new Exception('El anticipo ya ha sido regularizado, no se puede crear una nota de débito. En su lugar cree una nota de débito para el documento de regularización.');
    }

    return [
      'series' => $series->series,
      'number' => $this->nextDocumentNumber(
        ElectronicDocument::TYPE_NOTA_DEBITO,
        $series->series
      )['number']
    ];

  }

  /**
   * Sync electronic document to Dynamics 365
   *
   * @param int $id
   * @return array
   * @throws Exception
   */
  public function syncToDynamics(int $id): array
  {
    $document = $this->find($id);

    if (!$document) {
      throw new Exception('Documento electrónico no encontrado');
    }

    if ($document->anulado) {
      throw new Exception('No se puede sincronizar un documento anulado');
    }

    // Dispatch the sync job
    SyncSalesDocumentJob::dispatch($id);

    return [
      'success' => true,
      'message' => 'Sincronización con Dynamics iniciada correctamente',
      'document_id' => $id,
      'document_number' => $document->document_number,
    ];
  }

  /**
   * Get sync status for electronic document
   *
   * @param int $id
   * @return array
   * @throws Exception
   */
  public function getSyncStatus(int $id): array
  {
    $document = $this->find($id);

    if (!$document) {
      throw new Exception('Documento electrónico no encontrado');
    }

    // Get all migration logs for this document
    $logs = \App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $id)
      ->orderBy('step')
      ->get()
      ->map(function ($log) {
        return [
          'step' => $log->step,
          'table_name' => $log->table_name,
          'status' => $log->status,
          'proceso_estado' => $log->proceso_estado,
          'error_message' => $log->error_message,
          'attempts' => $log->attempts,
          'last_attempt_at' => $log->last_attempt_at,
          'completed_at' => $log->completed_at,
        ];
      });

    // Determine overall sync status
    $allCompleted = $logs->every(fn($log) => $log['status'] === 'completed');
    $anyFailed = $logs->contains(fn($log) => $log['status'] === 'failed');
    $anyInProgress = $logs->contains(fn($log) => $log['status'] === 'in_progress');

    $overallStatus = 'not_started';
    if ($logs->isNotEmpty()) {
      if ($allCompleted) {
        $overallStatus = 'completed';
      } elseif ($anyFailed) {
        $overallStatus = 'failed';
      } elseif ($anyInProgress) {
        $overallStatus = 'in_progress';
      } else {
        $overallStatus = 'pending';
      }
    }

    // Check intermediate database status if completed
    $dynamicsStatus = null;
    if ($allCompleted) {
      $tipoId = match ($document->sunat_concept_document_type_id) {
        ElectronicDocument::TYPE_FACTURA => '01',
        ElectronicDocument::TYPE_BOLETA => '03',
        ElectronicDocument::TYPE_NOTA_CREDITO => '07',
        ElectronicDocument::TYPE_NOTA_DEBITO => '08',
        default => '01',
      };

      $documentoId = "{$tipoId}-{$document->serie}-{$document->numero}";

      try {
        $dynamicsRecord = DB::connection('dbtp')
          ->table('neInTbVenta')
          ->where('EmpresaId', Company::AP_DYNAMICS)
          ->where('DocumentoId', $documentoId)
          ->first();

        if ($dynamicsRecord) {
          $dynamicsStatus = [
            'found' => true,
            'proceso_estado' => $dynamicsRecord->ProcesoEstado,
            'proceso_error' => $dynamicsRecord->ProcesoError,
            'fecha_proceso' => $dynamicsRecord->FechaProceso,
            'processed_by_dynamics' => $dynamicsRecord->ProcesoEstado === 1,
          ];
        }
      } catch (\Exception $e) {
        $dynamicsStatus = [
          'found' => false,
          'error' => $e->getMessage(),
        ];
      }
    }

    return [
      'document_id' => $id,
      'document_number' => $document->document_number,
      'overall_status' => $overallStatus,
      'sync_steps' => $logs,
      'dynamics_status' => $dynamicsStatus,
    ];
  }

  public function checkResources($id)
  {
    $document = $this->find($id);
    return [
      'sale' => new SalesDocumentDynamicsResource($document),
      'items' => $document->items()->get()->map(function ($item) use ($document) {
        return new SalesDocumentDetailDynamicsResource($item, $document);
      }),
      'series' => new SalesDocumentSerialDynamicsResource($document)
    ];
  }
}
