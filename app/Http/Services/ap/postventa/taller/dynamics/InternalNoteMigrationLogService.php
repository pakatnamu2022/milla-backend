<?php

namespace App\Http\Services\ap\postventa\taller\dynamics;

use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;

class InternalNoteMigrationLogService
{
  /**
   * Verifica si un log ha excedido el límite de intentos según su estado
   */
  public function hasExceededAttemptLimit(VehiclePurchaseOrderMigrationLog $log): bool
  {
    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_PENDING) {
      return $log->attempts >= VehiclePurchaseOrderMigrationLog::MAX_PENDING_ATTEMPTS;
    }

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS) {
      return $log->attempts >= VehiclePurchaseOrderMigrationLog::MAX_IN_PROGRESS_ATTEMPTS;
    }

    return false;
  }

  /**
   * Crea los logs necesarios para una nota interna (salida de inventario)
   */
  public function ensureInternalNoteLogsExist(ApInternalNote $internalNote): void
  {
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION,
      VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL,
    ];

    $tables = [
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION],
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL],
    ];

    foreach ($steps as $index => $step) {
      $transactionId = $this->buildInternalNoteTransactionId($internalNote, false);
      $this->getOrCreateLog(
        $internalNote->id,
        $step,
        $tables[$index],
        $transactionId
      );
    }
  }

  /**
   * Crea los logs necesarios para la reversión de una nota interna (ingreso de inventario)
   */
  public function ensureInternalNoteReversalLogsExist(ApInternalNote $internalNote): void
  {
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL,
      VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL,
    ];

    $tables = [
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL],
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL],
    ];

    foreach ($steps as $index => $step) {
      $transactionId = $this->buildInternalNoteTransactionId($internalNote, true);
      $this->getOrCreateLog(
        $internalNote->id,
        $step,
        $tables[$index],
        $transactionId
      );
    }
  }

  /**
   * Obtiene o crea un log de migración
   */
  public function getOrCreateLog(
    int $internalNoteId,
    string $step,
    string $tableName,
    string $externalId
  ): VehiclePurchaseOrderMigrationLog {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'internal_note_id' => $internalNoteId,
        'step' => $step,
      ],
      [
        'table_name' => $tableName,
        'external_id' => $externalId,
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'proceso_estado' => 0,
        'attempts' => 0,
      ]
    );
  }

  /**
   * Construye el TransaccionId para Dynamics
   * Formato: PS-IN-00001 (salida) o PI-IN-00001 (ingreso/reversión)
   * Debe coincidir con el formato usado en InternalNoteTransactionHeaderResource
   */
  public function buildInternalNoteTransactionId(ApInternalNote $internalNote, bool $isReversal): string
  {
    // SIEMPRE generar TransaccionId con el formato que usa el Resource
    // Salida: PS-IN-00045
    // Reversión/Ingreso: PI-IN-00045
    $prefix = $isReversal ? 'PI-' : 'PS-';
    $transactionId = $prefix . $internalNote->number;

    return $transactionId;
  }

  /**
   * Verifica si todos los pasos de migración están completados
   */
  public function checkAndUpdateCompletionStatus(ApInternalNote $internalNote, bool $isReversal = false): void
  {
    $steps = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL,
      ];

    $logs = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
      ->whereIn('step', $steps)
      ->get();

    // Verificar si todos los logs están completados
    $allCompleted = $logs->every(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED);

    // Verificar si hay algún log fallido
    $anyFailed = $logs->contains(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED);

    if ($allCompleted) {
      // Actualizar migration_status a 'completed'
      $internalNote->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_COMPLETED]);
    } elseif ($anyFailed) {
      // Actualizar migration_status a 'failed'
      $internalNote->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_FAILED]);
    } else {
      // Actualizar migration_status a 'in_progress'
      $internalNote->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_IN_PROGRESS]);
    }
  }
}