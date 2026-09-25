<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\facturacion\ApInternalNoteResource;
use App\Http\Services\BaseService;
use App\Jobs\BulkUpdateInternalNotesAccountingStatusJob;
use App\Jobs\UpdateInternalNoteAccountingStatusJob;
use App\Jobs\VerifyAndMigrateInternalNoteJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApInternalNoteService extends BaseService
{
  public function list(Request $request)
  {
    $query = ApInternalNote::withTrashed()
      ->whereHas('workOrder', function ($workOrderQuery) {
        $workOrderQuery->whereHas('items', function ($itemQuery) {
          $itemQuery->whereHas('typePlanning', function ($planningQuery) {
            $planningQuery->where('type_document', TypePlanningWorkOrder::INTERNA_SC);
          });
        });
      });

    return $this->getFilteredResults(
      $query,
      $request,
      ApInternalNote::filters,
      ApInternalNote::sorts,
      ApInternalNoteResource::class
    );
  }

  public function verifyInternalNoteMigration($id)
  {
    $internalNote = ApInternalNote::withTrashed()->find($id);

    if (!$internalNote) {
      return response()->json([
        'success' => false,
        'message' => 'Nota interna no encontrada',
      ], 404);
    }

    // Verificar si requiere procesamiento
    if (!in_array($internalNote->migration_status, [
      ApInternalNote::MIGRATION_STATUS_PENDING,
      ApInternalNote::MIGRATION_STATUS_IN_PROGRESS,
      ApInternalNote::MIGRATION_STATUS_FAILED,
    ])) {
      return response()->json([
        'success' => false,
        'message' => 'La nota interna no requiere procesamiento',
      ], 400);
    }

    // Determinar si es reversión (nota soft-deleted o logs de reversión existentes)
    $isReversal = $internalNote->trashed() ||
      VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
        ->where('step', 'LIKE', '%REVERSAL%')
        ->exists();

    // Capturar estado original
    $wasReset = false;

    // Si el estado es 'failed', resetear para reintentar
    if ($internalNote->migration_status === ApInternalNote::MIGRATION_STATUS_FAILED) {
      $wasReset = true;

      // Resetear nota interna a pending
      $internalNote->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_PENDING]);

      // Resetear logs relacionados a pending
      VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
        ->whereIn('step', [
          VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION,
          VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL,
          VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL,
          VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL,
        ])
        ->update([
          'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
          'error_message' => null,
        ]);
    }

    // Despachar job a la cola
    VerifyAndMigrateInternalNoteJob::dispatch($internalNote->id, $isReversal);

    return response()->json([
      'success' => true,
      'message' => 'Job de verificación de nota interna lanzado correctamente',
      'data' => [
        'internal_note_id' => $internalNote->id,
        'internal_note_number' => $internalNote->number,
        'is_reversal' => $isReversal,
        'is_deleted' => $internalNote->trashed(),
        'was_reset' => $wasReset,
        'migration_status' => $internalNote->migration_status,
      ],
    ]);
  }

  public function updateInternalNoteAccountingStatus($id)
  {
    $internalNote = ApInternalNote::withTrashed()->find($id);

    if (!$internalNote) {
      return response()->json([
        'success' => false,
        'message' => 'Nota interna no encontrada',
      ], 404);
    }

    // Validar que la nota no esté en estado skipped
    if ($internalNote->migration_status === ApInternalNote::MIGRATION_STATUS_SKIPPED) {
      return response()->json([
        'success' => false,
        'message' => 'La nota interna fue omitida porque no tiene repuestos cargados',
      ], 400);
    }

    // Despachar job a la cola de manera asíncrona
    UpdateInternalNoteAccountingStatusJob::dispatch($internalNote->id);

    return response()->json([
      'success' => true,
      'message' => 'Job de actualización de estado contable lanzado correctamente',
      'data' => [
        'internal_note_id' => $internalNote->id,
        'internal_note_number' => $internalNote->number,
        'status' => 'El proceso se ejecutará en segundo plano',
      ],
    ]);
  }

  /**
   * Actualiza masivamente el estado contable de las notas internas no verificadas
   */
  public function bulkUpdateAccountingStatus()
  {
    // Despachar job a la cola de manera asíncrona
    BulkUpdateInternalNotesAccountingStatusJob::dispatch();

    return response()->json([
      'success' => true,
      'message' => 'Job de actualización masiva de estado contable lanzado correctamente',
      'data' => [
        'status' => 'El proceso se ejecutará en segundo plano. Consulte los logs para ver el resultado.',
      ],
    ]);
  }
}
