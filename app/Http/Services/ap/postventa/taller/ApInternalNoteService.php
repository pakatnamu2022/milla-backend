<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\facturacion\ApInternalNoteResource;
use App\Http\Services\BaseService;
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
      VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
      VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
      VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
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
    if ($internalNote->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
      $wasReset = true;

      // Resetear nota interna a pending
      $internalNote->update(['migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING]);

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

    // Consultar ajustes de inventario en Dynamics
    $dynamicsAdjustments = $this->consultAjustesInventario();

    if (empty($dynamicsAdjustments)) {
      return response()->json([
        'success' => false,
        'message' => 'No se obtuvieron registros de ajustes de inventario desde Dynamics',
      ], 404);
    }

    // Crear un mapa para búsqueda rápida: [Numero][Tipo_Movimiento] = true
    $adjustmentsMap = [];
    foreach ($dynamicsAdjustments as $adjustment) {
      $numero = $adjustment->Numero ?? null;
      $tipoMovimiento = $adjustment->Tipo_Movimiento ?? null;

      if ($numero && $tipoMovimiento) {
        if (!isset($adjustmentsMap[$numero])) {
          $adjustmentsMap[$numero] = [];
        }
        $adjustmentsMap[$numero][$tipoMovimiento] = true;
      }
    }

    $updateData = [];

    // Verificar SALIDA (dyn_series_out)
    if ($internalNote->dyn_series_out && isset($adjustmentsMap[$internalNote->dyn_series_out]['SALIDA'])) {

      if (!$internalNote->is_accounted_out) {
        $updateData['is_accounted_out'] = true;
      }
    }

    // Verificar INGRESO (dyn_series_in)
    if ($internalNote->dyn_series_in && isset($adjustmentsMap[$internalNote->dyn_series_in]['INGRESO'])) {

      if (!$internalNote->is_accounted_in) {
        $updateData['is_accounted_in'] = true;
      }
    }

    // Actualizar solo si hay cambios
    $updated = false;
    if (!empty($updateData)) {
      $internalNote->update($updateData);
      $updated = true;

      // LOG 5: Refrescar y mostrar estado después de actualizar
      $internalNote->refresh();
    }

    return response()->json([
      'success' => true,
      'message' => $updated ? 'Nota interna actualizada correctamente' : 'No se encontraron cambios para actualizar',
      'data' => [
        'updated' => $updated,
        'internal_note_id' => $internalNote->id,
        'internal_note_number' => $internalNote->number,
        'is_accounted_in' => $internalNote->is_accounted_in,
        'is_accounted_out' => $internalNote->is_accounted_out,
      ],
    ]);
  }

  /**
   * Consulta los ajustes de inventario en Dynamics
   */
  protected function consultAjustesInventario(): array
  {
    try {
      return DB::connection(Company::CONNECTION_DYNAMICS_3)
        ->select("EXEC neIvConsultarAjustesInventario");
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarAjustesInventario', [
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }
}
