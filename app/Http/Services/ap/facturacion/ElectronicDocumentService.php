<?php

namespace App\Http\Services\ap\facturacion;

use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use App\Models\ap\facturacion\ElectronicDocumentGuide;
use App\Models\ap\facturacion\ElectronicDocumentInstallment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

  /**
   * Create a new electronic document
   * @throws Exception
   */
  public function store(mixed $data): ElectronicDocumentResource
  {
    DB::beginTransaction();
    try {
      // Validar y calcular el siguiente número correlativo si no se proporciona
      if (!isset($data['numero'])) {
        $data['numero'] = ElectronicDocument::getNextNumber(
          $data['ap_billing_document_type_id'],
          $data['serie']
        );
      }

      // Validar que la serie sea correcta
      if (!ElectronicDocument::validateSerie($data['ap_billing_document_type_id'], $data['serie'])) {
        throw new Exception('La serie no es válida para el tipo de documento seleccionado');
      }

      // Crear el documento principal
      $document = ElectronicDocument::create(array_merge($data, [
        'created_by' => auth()->id(),
        'status' => ElectronicDocument::STATUS_DRAFT,
      ]));

      // Crear los items
      if (isset($data['items']) && is_array($data['items'])) {
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

      DB::commit();
      return new ElectronicDocumentResource($document->load(['items', 'guides', 'installments']));
    } catch (Exception $e) {
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

      // Procesar respuesta
      if ($response['aceptada_por_sunat']) {
        $document->markAsAccepted($response);
        $message = 'Documento enviado y aceptado por SUNAT correctamente';
      } else {
        $document->markAsRejected(
          $response['sunat_description'] ?? 'Error desconocido',
          $response
        );
        $message = 'Documento enviado pero rechazado por SUNAT: ' . ($response['sunat_description'] ?? 'Error desconocido');
      }

      DB::commit();

      return response()->json([
        'success' => $response['aceptada_por_sunat'],
        'message' => $message,
        'data' => new ElectronicDocumentResource($document->fresh()),
        'sunat_response' => $response
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

      // Actualizar estado si cambió
      if (isset($response['aceptada_por_sunat']) && $response['aceptada_por_sunat'] && !$document->aceptada_por_sunat) {
        DB::beginTransaction();
        $document->markAsAccepted($response);
        DB::commit();
      }

      return response()->json([
        'success' => true,
        'message' => 'Estado consultado correctamente',
        'data' => new ElectronicDocumentResource($document->fresh()),
        'sunat_response' => $response
      ]);
    } catch (Exception $e) {
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

      // Enviar anulación a Nubefact
      $response = $this->nubefactService->cancelDocument($document, $reason);

      // Marcar como cancelado
      $document->markAsCancelled($reason);

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

      // Preparar datos de la nota de crédito
      $creditNoteData = array_merge($data, [
        'ap_billing_document_type_id' => ElectronicDocument::TYPE_NOTA_CREDITO,
        'documento_que_se_modifica_tipo' => $originalDocument->ap_billing_document_type_id,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'origin_module' => $originalDocument->origin_module,
        'origin_entity_type' => $originalDocument->origin_entity_type,
        'origin_entity_id' => $originalDocument->origin_entity_id,
      ]);

      // Crear la nota de crédito
      $creditNote = $this->store($creditNoteData);

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

      // Preparar datos de la nota de débito
      $debitNoteData = array_merge($data, [
        'ap_billing_document_type_id' => ElectronicDocument::TYPE_NOTA_DEBITO,
        'documento_que_se_modifica_tipo' => $originalDocument->ap_billing_document_type_id,
        'documento_que_se_modifica_serie' => $originalDocument->serie,
        'documento_que_se_modifica_numero' => $originalDocument->numero,
        'origin_module' => $originalDocument->origin_module,
        'origin_entity_type' => $originalDocument->origin_entity_type,
        'origin_entity_id' => $originalDocument->origin_entity_id,
      ]);

      // Crear la nota de débito
      $debitNote = $this->store($debitNoteData);

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
      } elseif ($igvTypeCode == 9) { // Exonerado
        $totals['total_exonerada'] += $item['subtotal'];
      } elseif ($igvTypeCode == 10) { // Inafecto
        $totals['total_inafecta'] += $item['subtotal'];
      } elseif ($igvTypeCode == 21) { // Gratuito
        $totals['total_gratuita'] += $item['subtotal'];
      }
    }

    // Calcular total (las operaciones gratuitas no suman)
    $totals['total'] = $totals['total_gravada'] + $totals['total_exonerada'] + $totals['total_inafecta'] + $totals['total_igv'];

    return $totals;
  }

  public function show(int $id)
  {
    $electronicDocument = ElectronicDocument::find($id);
    return new ElectronicDocumentResource($electronicDocument);
  }
}
