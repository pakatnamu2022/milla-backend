<?php

namespace App\Http\Controllers\ap\facturacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\facturacion\ElectronicDocumentReportRequest;
use App\Http\Requests\ap\facturacion\IndexElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\NextCorrelativeElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\StoreCreditNoteRequest;
use App\Http\Requests\ap\facturacion\StoreDebitNoteRequest;
use App\Http\Requests\ap\facturacion\StoreElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\UpdateCreditNoteRequest;
use App\Http\Requests\ap\facturacion\UpdateDebitNoteRequest;
use App\Http\Requests\ap\facturacion\UpdateElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\StoreConsolidatedInvoiceRequest;
use App\Http\Requests\ap\facturacion\RegularizeAdvancePaymentRequest;
use App\Http\Requests\ap\facturacion\StoreHistoricalAdvancePaymentRequest;
use App\Http\Requests\ap\facturacion\ExportElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\StoreHistoricalFinalSaleRequest;
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderMigrationLogResource;
use App\Http\Resources\Dynamics\SalesDocumentPreviewResource;
use App\Http\Resources\Dynamics\TraverseAdjustmentHeaderResource;
use App\Http\Resources\Dynamics\TraverseAdjustmentDetailResource;
use App\Http\Resources\Dynamics\TraverseAccountingEntryHeaderResource;
use App\Http\Resources\Dynamics\TraverseAccountingEntryDetailResource;
use App\Http\Services\ap\facturacion\ElectronicDocumentService;
use App\Http\Services\Billing\TraverseMigrationLogService;
use App\Http\Services\ap\postventa\gestionProductos\InventoryOutputValidationService;
use App\Jobs\SyncAccountingStatusJob;
use App\Http\Traits\HasApiResponse;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Services\Billing\AssociatePurchaseTraverseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ElectronicDocumentController extends Controller
{
  use HasApiResponse;

  protected ElectronicDocumentService $service;

  public function __construct(ElectronicDocumentService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of electronic documents
   */
  public function index(IndexElectronicDocumentRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display a simplified listing of electronic documents
   * Returns only essential fields for table view
   */
  public function indexSimplified(IndexElectronicDocumentRequest $request): JsonResponse
  {
    try {
      return $this->service->listSimplified($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display a listing of invoices and tickets only (excluding credit notes and associated documents)
   */
  public function listInvoicesAndTickets(IndexElectronicDocumentRequest $request): JsonResponse
  {
    try {
      return $this->service->listInvoicesAndTickets($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get next correlative document number
   * @param NextCorrelativeElectronicDocumentRequest $request
   * @return JsonResponse
   */
  public function nextDocumentNumber(NextCorrelativeElectronicDocumentRequest $request): JsonResponse
  {
    try {
      $series = AssignSalesSeries::find($request->input('series'));
      return $this->success($this->service->nextDocumentNumber(
        $request->input('document_type'),
        $series->series
      ));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a newly created electronic document
   */
  public function store(StoreElectronicDocumentRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified electronic document
   */
  public function show($id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update the specified electronic document
   */
  public function update(UpdateElectronicDocumentRequest $request, $id): JsonResponse
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      $document = $this->service->update($data);

      return $this->success([
        'success' => true,
        'message' => 'Documento electrónico actualizado correctamente',
        'data' => $document
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Remove the specified electronic document
   */
  public function destroy($id): JsonResponse
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Preview the payload that would be sent to Nubefact without sending it
   */
  public function previewNubefactPayload($id): JsonResponse
  {
    try {
      return $this->success($this->service->previewNubefactPayload($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Send document to Nubefact/SUNAT
   */
  public function sendToNubefact($id): JsonResponse
  {
    try {
      return $this->service->sendToNubefact($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Query document status from Nubefact
   */
  public function queryFromNubefact($id): JsonResponse
  {
    try {
      return $this->service->queryFromNubefact($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Cancel document in Nubefact (Comunicación de baja)
   */
  public function cancelInNubefact(Request $request, $id): JsonResponse
  {
    $request->validate([
      'reason' => 'required|string|min:10|max:250',
      'will_reinvoice' => 'nullable|boolean'
    ]);

    try {
      return $this->service->cancelInNubefact(
        $id,
        $request->input('reason'),
        $request->input('will_reinvoice', false) // Default false
      );
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Pre-cancel document in Nubefact (Comunicación de baja)
   * @param $id
   * @return JsonResponse
   */
  public function preCancelInNubefact($id): JsonResponse
  {
    try {
      return $this->success($this->service->preCancelInNubefact($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Create credit note from existing document
   */
  public function createCreditNote(StoreCreditNoteRequest $request, $id): JsonResponse
  {
    try {
      $data = $request->validated();

      $creditNote = $this->service->createCreditNote($id, $data);

      return $this->success([
        'success' => true,
        'message' => 'Nota de crédito creada correctamente',
        'data' => $creditNote
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update credit note
   */
  public function updateCreditNote(UpdateCreditNoteRequest $request, $id): JsonResponse
  {
    try {
      $data = $request->validated();

      $creditNote = $this->service->updateCreditNote($id, $data);

      return $this->success([
        'success' => true,
        'message' => 'Nota de crédito actualizada correctamente',
        'data' => $creditNote
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Create debit note from existing document
   */
  public function createDebitNote(StoreDebitNoteRequest $request, $id): JsonResponse
  {
    try {
      $data = $request->validated();
      $data['original_document_id'] = $id;

      $debitNote = $this->service->createDebitNote($id, $data);

      return $this->success([
        'success' => true,
        'message' => 'Nota de débito creada correctamente',
        'data' => $debitNote
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update debit note
   */
  public function updateDebitNote(UpdateDebitNoteRequest $request, $id): JsonResponse
  {
    try {
      $data = $request->validated();

      $debitNote = $this->service->updateDebitNote($id, $data);

      return $this->success([
        'success' => true,
        'message' => 'Nota de débito actualizada correctamente',
        'data' => $debitNote
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get documents by origin entity
   */
  public function getByOriginEntity($areaId, $entityType, $entityId): JsonResponse
  {
    try {
      return $this->service->getByOriginEntity($areaId, $entityType, $entityId);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate PDF for electronic document
   */
  public function generatePDF($id)
  {
    try {
      $pdf = $this->service->generatePDF($id);

      // Get document info for filename
      $document = $this->service->find($id);
      $filename = "documento-electronico-{$document->serie}-{$document->numero}.pdf";

      return $pdf->download($filename);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function nextCreditNoteNumber(NextCorrelativeElectronicDocumentRequest $request, $id): JsonResponse
  {
    try {
      return $this->success($this->service->nextCreditNoteNumber($request->validated(), $id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function nextDebitNoteNumber(NextCorrelativeElectronicDocumentRequest $request, $id): JsonResponse
  {
    try {
      return $this->success($this->service->nextDebitNoteNumber($request->validated(), $id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Sync electronic document to Dynamics 365
   */
  public function syncToDynamics($id): JsonResponse
  {
    try {
      return $this->success($this->service->syncToDynamics($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Despacha jobs de migración para todos los documentos electrónicos no completados
   */
  public function dispatchAll(): JsonResponse
  {
    try {
      return $this->success($this->service->dispatchAll());
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Despacha manualmente el job de sincronización (útil para reintentar fallidos)
   */
  public function dispatchMigration(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->dispatchMigration($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function resetMigration(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->resetMigration($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get sync status for electronic document
   */
  public function getSyncStatus($id): JsonResponse
  {
    try {
      return $this->success($this->service->getSyncStatus($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function checkResources($id): JsonResponse
  {
    try {
      return $this->success($this->service->checkResources($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get migration logs for a specific electronic document
   */
  public function logs(int $id): JsonResponse
  {
    try {
      $electronicDocument = ElectronicDocument::find($id);

      if (!$electronicDocument) {
        return response()->json([
          'success' => false,
          'message' => 'Documento electrónico no encontrado',
        ], 404);
      }

      $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $id)
        ->orderBy('id')
        ->get();

      return response()->json([
        'electronic_document' => [
          'id' => $electronicDocument->id,
          'full_number' => $electronicDocument->full_number,
          'serie' => $electronicDocument->serie,
          'numero' => $electronicDocument->numero,
          'migration_status' => $electronicDocument->migration_status,
          'migrated_at' => $electronicDocument->migrated_at,
          'created_at' => $electronicDocument->created_at->format('Y-m-d H:i:s'),
        ],
        'logs' => VehiclePurchaseOrderMigrationLogResource::collection($logs),
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get detailed migration history for an electronic document
   */
  public function history(int $id): JsonResponse
  {
    try {
      $electronicDocument = ElectronicDocument::find($id);

      if (!$electronicDocument) {
        return response()->json([
          'success' => false,
          'message' => 'Documento electrónico no encontrado',
        ], 404);
      }

      $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $id)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();

      // Crear timeline de eventos
      $timeline = $logs->map(function ($log) {
        $events = [];

        // Evento de creación
        $events[] = [
          'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
          'event' => 'created',
          'description' => "Paso '{$log->step}' creado",
          'status' => 'pending',
        ];

        // Eventos de intentos
        if ($log->last_attempt_at) {
          $events[] = [
            'timestamp' => $log->last_attempt_at->format('Y-m-d H:i:s'),
            'event' => 'attempt',
            'description' => "Intento #{$log->attempts} de sincronización",
            'status' => $log->status,
            'error' => $log->error_message,
          ];
        }

        // Evento de completado
        if ($log->completed_at) {
          $events[] = [
            'timestamp' => $log->completed_at->format('Y-m-d H:i:s'),
            'event' => 'completed',
            'description' => "Paso completado exitosamente",
            'status' => 'completed',
            'proceso_estado' => $log->proceso_estado,
          ];
        }

        return [
          'step' => $log->step,
          'step_name' => (new VehiclePurchaseOrderMigrationLogResource($log))->step_name,
          'events' => $events,
        ];
      });

      return response()->json([
        'electronic_document' => [
          'id' => $electronicDocument->id,
          'full_number' => $electronicDocument->full_number,
          'serie' => $electronicDocument->serie,
          'numero' => $electronicDocument->numero,
          'migration_status' => $electronicDocument->migration_status,
          'migrated_at' => $electronicDocument->migrated_at?->format('Y-m-d H:i:s'),
        ],
        'timeline' => $timeline,
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Sync accounting status (is_accounted, is_annulled) from Dynamics for all
   * documents that have been requested to Dynamics and whose migration is completed.
   */
  public function syncAccountingStatus(): JsonResponse
  {
    SyncAccountingStatusJob::dispatch();
    return $this->success(['message' => 'Sincronización de estados contables iniciada en segundo plano.']);
  }

  /**
   * Sync accounting status for a single document synchronously and return the result.
   *
   * IMPORTANTE: Para áreas 881 (TALLER) y 882 (MESON), este endpoint valida
   * ANTES de ejecutar el Job que hay stock suficiente, reservas correctas, etc.
   * Si hay errores en la validación, NO se ejecuta el Job y se retorna el error.
   */
  public function syncAccountingStatusForDocument(int $id): JsonResponse
  {
    try {
      $document = ElectronicDocument::findOrFail($id);

      // Si el documento ya está contabilizado, no ejecutar nada
      if ($document->is_accounted && $document->status !== ElectronicDocument::STATUS_CANCELLED) {
        return response()->json([
          'message' => 'El documento ya está contabilizado, no se puede procesar nuevamente',
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'is_accounted' => true,
        ], 422);
      }

      // VALIDACIÓN PRE-EJECUCIÓN para áreas 881 (TALLER) y 882 (MESON)
      // Solo para documentos que NO son notas de crédito/débito
      $validationService = new InventoryOutputValidationService();
      $validation = $validationService->validateInventoryOutput($id);

      // Si la validación falló, retornar error SIN ejecutar el Job
      if (!$validation['valid'] && $document->status !== ElectronicDocument::STATUS_CANCELLED && !$document->anulado) {
        return response()->json([
          'message' => 'No se puede procesar el comprobante debido a problemas de inventario',
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'area_id' => $document->area_id,
          'errors' => $validation['errors'],
          'details' => $validation['details'],
        ], 422);
      }

      // Si la validación pasó (o no era necesaria), ejecutar el Job
      SyncAccountingStatusJob::dispatchSync($id);
      $document->refresh();

      return $this->success([
        'id' => $document->id,
        'full_number' => $document->full_number,
        'is_accounted' => $document->is_accounted,
        'is_annulled' => $document->is_annulled,
        'validation' => [
          'performed' => in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON]),
          'result' => $validation['valid'] ? 'passed' : 'skipped',
        ],
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate report of electronic documents
   */
  public function report(ElectronicDocumentReportRequest $request): JsonResponse
  {
    try {
      $filters = $request->toReportFilters();
      $model = new ElectronicDocument();
      $data = $model->getReportData($filters);

      // Transform data to include only reportColumns
      $columns = $model->getReportableColumns();
      $reportData = $data->map(function ($item) use ($columns) {
        $row = [];
        foreach ($columns as $column => $config) {
          $value = data_get($item, $column);

          // Apply formatter if specified
          if (isset($config['formatter']) && $value !== null) {
            switch ($config['formatter']) {
              case 'date':
                $value = $value instanceof Carbon ? $value->format('d/m/Y') : $value;
                break;
              case 'datetime':
                $value = $value instanceof Carbon ? $value->format('d/m/Y H:i:s') : $value;
                break;
              case 'boolean':
                $value = $value ? 'Sí' : 'No';
                break;
            }
          }

          $row[$config['label']] = $value;
        }
        return $row;
      });

      return $this->success([
        'data' => $reportData,
        'total' => $reportData->count(),
        'columns' => array_values(array_map(fn($col) => $col['label'], $columns)),
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Create a consolidated invoice from multiple work orders' internal notes
   */
  public function createConsolidatedInvoice(StoreConsolidatedInvoiceRequest $request): JsonResponse
  {
    try {
      $result = $this->service->createConsolidatedInvoice($request->validated());

      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get invoice with internal notes and their work orders
   *
   * @param int $id Invoice ID
   * @return JsonResponse
   */
  public function getInvoiceWithWorkOrders(int $id): JsonResponse
  {
    try {
      $result = $this->service->getInvoiceWithWorkOrders($id);

      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Regulariza/registra un anticipo con valores predefinidos
   * Este endpoint NO envía a Nubefact, solo registra el anticipo como referencia
   * Ya marca como aceptada_por_sunat = 1 y status = 'accepted'
   *
   * @param RegularizeAdvancePaymentRequest $request
   * @return JsonResponse
   */
  public function regularizeAdvancePayment(RegularizeAdvancePaymentRequest $request): JsonResponse
  {
    try {
      $document = $this->service->regularizeAdvancePayment($request->validated());

      return $this->success([
        'success' => true,
        'message' => 'Anticipo regularizado correctamente',
        'data' => $document
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }


  /**
   * Registra un anticipo histórico con valores predefinidos
   * @param StoreHistoricalAdvancePaymentRequest $request
   * @return JsonResponse
   */
  public function registerHistoricalAdvance(StoreHistoricalAdvancePaymentRequest $request): JsonResponse
  {
    try {
      $document = $this->service->registerHistoricalAdvance($request->validated());

      return $this->success([
        'success' => true,
        'message' => 'Anticipo histórico registrado correctamente',
        'data' => $document,
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function registerHistoricalFinalSale(StoreHistoricalFinalSaleRequest $request): JsonResponse
  {
    try {
      $document = $this->service->registerHistoricalFinalSale($request->validated());

      return $this->success([
        'success' => true,
        'message' => 'Venta final histórica registrada correctamente',
        'data' => $document,
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Registro masivo de ventas finales históricas desde un Excel (stock inicial ya
   * vendido/entregado sin factura). dry_run = true sólo analiza, no persiste.
   */
  public function bulkRegisterHistoricalFinalSale(Request $request): JsonResponse
  {
    try {
      $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv',
        'dry_run' => 'nullable|boolean',
      ]);
      $dryRun = $request->boolean('dry_run', true);

      return $this->success(
        $this->service->bulkRegisterHistoricalFinalSale($request->file('file'), $dryRun)
      );
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a newly created electronic document for historical final sale with advance
   */
  public function registerHistoricalFinalSaleWithAdvance(StoreElectronicDocumentRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->storeHistoricalFinalSaleWithAdvance($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function exportParams(): JsonResponse
  {
    try {
      return $this->success($this->service->exportParams());
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function export(ExportElectronicDocumentRequest $request)
  {
    try {
      return $this->service->export($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Previsualiza los datos que se enviarían a Dynamics
   * Funciona para facturas SIMPLES y MASIVAS:
   *
   * - SIMPLE: Usa los items del documento directamente (ap_billing_electronic_document_items)
   *           Incluye accesorios de postventa, deducibles y repuestos en travesía consolidados
   *
   * - MASSIVE: Usa las órdenes de trabajo vinculadas a través de electronic_document_internal_notes
   *            Procesa parts y labours de cada work order
   *
   * @param int $id ID del comprobante electrónico
   * @return JsonResponse
   */
  public function previewDynamicsPayload(int $id): JsonResponse
  {
    try {
      $document = ElectronicDocument::find($id);

      if (!$document) {
        return response()->json(['message' => 'Documento electrónico no encontrado'], 404);
      }

      $preview = new SalesDocumentPreviewResource($document);

      return response()->json($preview);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Asociar productos en travesía con items de compra
   *
   * @param int $id ID del comprobante electrónico
   * @param Request $request
   * @return JsonResponse
   */
  public function associatePurchaseTraverse(int $id, Request $request): JsonResponse
  {
    try {
      $request->validate([
        'purchase_order_item_ids' => 'required|array|min:1',
        'purchase_order_item_ids.*' => 'required|integer|exists:ap_purchase_order_item,id',
      ]);

      $service = new AssociatePurchaseTraverseService();
      $result = $service->associate($id, $request->input('purchase_order_item_ids'));

      return $this->success($result, 'Asociación realizada exitosamente.');
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Revertir asociación de productos en travesía
   *
   * @param int $id ID del comprobante electrónico
   * @return JsonResponse
   */
  public function revertPurchaseTraverse(int $id): JsonResponse
  {
    try {
      $service = new AssociatePurchaseTraverseService();
      $result = $service->revert($id);

      return $this->success($result, 'Asociación revertida exitosamente.');
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Previsualiza los datos que se migrarán a Dynamics para travesía
   * Muestra la información que se enviará a las 4 tablas:
   * - neInTbTransaccionInventario (Adjustment Header)
   * - neInTbTransaccionInventarioDet (Adjustment Detail)
   * - neInTbIntegracionAsientoCab (Accounting Entry Header)
   * - neInTbIntegracionAsientoDet (Accounting Entry Detail)
   *
   * Acepta parámetro opcional ?is_reversal=1 para previsualizar reversión
   *
   * @param int $id ID del comprobante electrónico
   * @param Request $request
   * @return JsonResponse
   */
  public function previewTraverseDynamicsPayload(int $id, Request $request): JsonResponse
  {
    try {
      $document = ElectronicDocument::with([
        'seriesModel.sede',
        'items.product.articleClass',
        'items.product.unitMeasurement',
        'items.linkTransactions.purchaseOrderItem.product',
        'items.linkTransactions.purchaseOrderItem.purchaseOrder',
        'items.linkTransactions.creator.person',
        'creator.person',
        'currency',
        'exchangeRate',
      ])->find($id);

      if (!$document) {
        return response()->json(['message' => 'Documento electrónico no encontrado'], 404);
      }

      // Verificar que tenga items en travesía
      $hasTraverseItems = $document->items()
        ->where('is_traverse', true)
        ->whereNotNull('product_id')
        ->exists();

      if (!$hasTraverseItems) {
        return response()->json([
          'message' => 'El documento no tiene items en travesía con product_id válido'
        ], 422);
      }

      // Obtener parámetro de reversión
      $isReversal = $request->boolean('is_reversal', false);

      // Obtener la primera transacción activa para obtener la fecha y el creador
      $firstTransaction = null;
      foreach ($document->items as $item) {
        $transaction = $item->linkTransactions()->where('status', 'active')->first();
        if ($transaction) {
          $firstTransaction = $transaction;
          break;
        }
      }

      if (!$firstTransaction) {
        return response()->json([
          'message' => 'No se encontraron transacciones activas para obtener la fecha y el creador'
        ], 422);
      }

      // Obtener la fecha del created_at de la transacción
      $transactionDate = $firstTransaction->created_at->format('Y-m-d');

      // Obtener el DNI del creador de la transacción
      $creatorVat = $firstTransaction->creator && $firstTransaction->creator->person
        ? $firstTransaction->creator->person->vat
        : null;

      if (!$creatorVat) {
        return response()->json([
          'message' => 'No se pudo obtener el DNI del creador de la transacción'
        ], 422);
      }

      $logService = new TraverseMigrationLogService();

      // Generar los detalles de ajuste de inventario
      $adjustmentDetailResource = new TraverseAdjustmentDetailResource($document, $isReversal);
      $adjustmentDetailLines = $adjustmentDetailResource->toArray($request);

      // Generar los detalles contables (pasando el DNI del creador)
      $asientoNumber = $logService->getNextAsientoNumber();
      $accountingDetailResource = new TraverseAccountingEntryDetailResource($document, $asientoNumber, $isReversal, $creatorVat);
      $accountingDetailLines = $accountingDetailResource->toArray($request);

      // Generar header de ajuste de inventario
      $adjustmentHeaderResource = new TraverseAdjustmentHeaderResource($document, $isReversal);
      $adjustmentHeaderData = $adjustmentHeaderResource->toArray($request);

      // Usar la fecha de la transacción para el ajuste
      $adjustmentHeaderData['FechaEmision'] = $transactionDate;
      $adjustmentHeaderData['FechaContable'] = $transactionDate;

      // Generar header contable (pasando la fecha de la transacción y el DNI del creador)
      $accountingHeaderResource = new TraverseAccountingEntryHeaderResource($document, $asientoNumber, $isReversal, $transactionDate, $creatorVat);
      $accountingHeaderData = $accountingHeaderResource->toArray($request);

      // Usar la fecha de la transacción para el asiento contable
      $accountingHeaderData['Fecha'] = $transactionDate;

      // Generar un solo movimiento con todos los detalles
      $movimientos = [
        [
          'transaction_date' => $transactionDate,
          'creator_vat' => $creatorVat,
          'paso_1_adjustment' => [
            'table_header' => 'neInTbTransaccionInventario',
            'table_detail' => 'neInTbTransaccionInventarioDet',
            'header' => $adjustmentHeaderData,
            'details' => $adjustmentDetailLines,
          ],
          'paso_2_accounting' => [
            'table_header' => 'neInTbIntegracionAsientoCab',
            'table_detail' => 'neInTbIntegracionAsientoDet',
            'header' => $accountingHeaderData,
            'details' => $accountingDetailLines,
          ],
        ]
      ];

      return response()->json([
        'document_info' => [
          'id' => $document->id,
          'full_number' => $document->full_number,
          'serie' => $document->serie,
          'numero' => $document->numero,
          'fecha_emision' => $document->fecha_de_emision ? $document->fecha_de_emision->format('Y-m-d') : null,
          'traverse_migration_status' => $document->traverse_migration_status,
          'associate_purchase_traverse' => $document->associate_purchase_traverse,
        ],
        'preview_mode' => $isReversal ? 'REVERSIÓN' : 'ASOCIACIÓN NORMAL',
        'dynamics_payload' => $movimientos,
        'summary' => [
          'transaction_date' => $transactionDate,
          'creator_vat' => $creatorVat,
          'total_movements' => count($movimientos),
          'total_adjustment_details' => count($adjustmentDetailLines),
          'total_accounting_details' => count($accountingDetailLines),
        ],
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Obtener historial detallado de migración de travesía para un documento electrónico
   * Muestra timeline de eventos para los 4 pasos de travesía (normal o reversión):
   * - Ajuste de inventario (header y detail)
   * - Asiento contable (header y detail)
   *
   * @param int $id ID del comprobante electrónico
   * @return JsonResponse
   */
  public function traverseHistory(int $id): JsonResponse
  {
    try {
      $electronicDocument = ElectronicDocument::find($id);

      if (!$electronicDocument) {
        return response()->json([
          'success' => false,
          'message' => 'Documento electrónico no encontrado',
        ], 404);
      }

      // Obtener todos los logs de travesía (normal y reversión)
      $traverseSteps = [
        // Normal
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL,
        // Reversión
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL_REVERSAL,
      ];

      $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $id)
        ->whereIn('step', $traverseSteps)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();

      // Crear timeline de eventos
      $timeline = $logs->map(function ($log) {
        $events = [];

        // Nombre del paso según el step
        $stepNames = [
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT => 'Ajuste de Inventario - Header',
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL => 'Ajuste de Inventario - Detail',
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER => 'Asiento Contable - Header',
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL => 'Asiento Contable - Detail',
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_REVERSAL => 'Ajuste de Inventario - Header (Reversión)',
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL_REVERSAL => 'Ajuste de Inventario - Detail (Reversión)',
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER_REVERSAL => 'Asiento Contable - Header (Reversión)',
          VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL_REVERSAL => 'Asiento Contable - Detail (Reversión)',
        ];

        // Evento de creación
        $events[] = [
          'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
          'event' => 'created',
          'description' => "Paso '{$log->step}' creado",
          'status' => 'pending',
        ];

        // Eventos de intentos
        if ($log->last_attempt_at) {
          $events[] = [
            'timestamp' => $log->last_attempt_at->format('Y-m-d H:i:s'),
            'event' => 'attempt',
            'description' => "Intento #{$log->attempts} de sincronización",
            'status' => $log->status,
            'error' => $log->error_message,
          ];
        }

        // Evento de completado
        if ($log->completed_at) {
          $events[] = [
            'timestamp' => $log->completed_at->format('Y-m-d H:i:s'),
            'event' => 'completed',
            'description' => "Paso completado exitosamente",
            'status' => 'completed',
            'proceso_estado' => $log->proceso_estado,
          ];
        }

        return [
          'step' => $log->step,
          'step_name' => $stepNames[$log->step] ?? $log->step,
          'table_name' => $log->table_name,
          'external_id' => $log->external_id,
          'current_status' => $log->status,
          'attempts' => $log->attempts,
          'events' => $events,
        ];
      });

      return response()->json([
        'electronic_document' => [
          'id' => $electronicDocument->id,
          'full_number' => $electronicDocument->full_number,
          'serie' => $electronicDocument->serie,
          'numero' => $electronicDocument->numero,
          'traverse_migration_status' => $electronicDocument->traverse_migration_status,
          'traverse_migrated_at' => $electronicDocument->traverse_migrated_at?->format('Y-m-d H:i:s'),
        ],
        'timeline' => $timeline,
        'summary' => [
          'total_steps' => $logs->count(),
          'completed_steps' => $logs->where('status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)->count(),
          'failed_steps' => $logs->where('status', VehiclePurchaseOrderMigrationLog::STATUS_FAILED)->count(),
          'in_progress_steps' => $logs->where('status', VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS)->count(),
          'pending_steps' => $logs->where('status', VehiclePurchaseOrderMigrationLog::STATUS_PENDING)->count(),
        ],
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
