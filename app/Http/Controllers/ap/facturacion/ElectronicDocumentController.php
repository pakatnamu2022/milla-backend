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
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderMigrationLogResource;
use App\Http\Services\ap\facturacion\ElectronicDocumentService;
use App\Http\Traits\HasApiResponse;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
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
//      throw new Exception(json_encode($request->validated()));
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
      'reason' => 'required|string|min:10|max:250'
    ]);

    try {
      return $this->service->cancelInNubefact($id, $request->input('reason'));
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
      $data['original_document_id'] = $id;

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
}
