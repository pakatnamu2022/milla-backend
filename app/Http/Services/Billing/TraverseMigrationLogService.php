<?php

namespace App\Http\Services\Billing;

use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Support\Facades\DB;

class TraverseMigrationLogService
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
   * Crea los logs necesarios para la travesía (ajuste de inventario)
   */
  public function ensureTraverseAdjustmentLogsExist(ElectronicDocument $document, bool $isReversal = false): void
  {
    $steps = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL,
      ];

    $tables = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_REVERSAL],
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL_REVERSAL],
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT],
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL],
      ];

    foreach ($steps as $index => $step) {
      $transactionId = $this->buildTraverseTransactionId($document, $isReversal);
      $this->getOrCreateLog(
        $document->id,
        $step,
        $tables[$index],
        $transactionId
      );
    }
  }

  /**
   * Crea los logs necesarios para el asiento contable de travesía
   */
  public function ensureTraverseAccountingLogsExist(ElectronicDocument $document, bool $isReversal = false): void
  {
    $steps = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL,
      ];

    $tables = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER_REVERSAL],
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL_REVERSAL],
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER],
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL],
      ];

    foreach ($steps as $index => $step) {
      $referencia = $this->buildTraverseReferencia($document, $isReversal);
      $this->getOrCreateLog(
        $document->id,
        $step,
        $tables[$index],
        $referencia
      );
    }
  }

  /**
   * Obtiene o crea un log de migración
   */
  public function getOrCreateLog(
    int $electronicDocumentId,
    string $step,
    string $tableName,
    string $externalId
  ): VehiclePurchaseOrderMigrationLog {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'electronic_document_id' => $electronicDocumentId,
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
   * Construye el TransaccionId para el ajuste de inventario
   * Formato: TRV-{id} (salida) o TRV-{id}* (ingreso/reversión)
   */
  public function buildTraverseTransactionId(ElectronicDocument $document, bool $isReversal): string
  {
    $transactionId = "TRV-{$document->id}";

    if ($isReversal) {
      $transactionId .= '*';
    }

    return $transactionId;
  }

  /**
   * Construye la Referencia para el asiento contable
   * Formato: TRV-{serie}-{numero} (salida) o TRV-{serie}-{numero}-REV (reversión)
   */
  public function buildTraverseReferencia(ElectronicDocument $document, bool $isReversal): string
  {
    $referencia = "TRV-{$document->serie}-{$document->numero}";

    if ($isReversal) {
      $referencia .= '-REV';
    }

    return $referencia;
  }

  /**
   * Verifica si todos los pasos de ajuste de inventario están completados
   */
  public function checkAdjustmentCompletionStatus(ElectronicDocument $document, bool $isReversal = false): bool
  {
    $steps = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL,
      ];

    $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->whereIn('step', $steps)
      ->get();

    if ($logs->isEmpty()) {
      return false;
    }

    return $logs->every(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED);
  }

  /**
   * Verifica si todos los pasos de asiento contable están completados
   */
  public function checkAccountingCompletionStatus(ElectronicDocument $document, bool $isReversal = false): bool
  {
    $steps = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL,
      ];

    $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->whereIn('step', $steps)
      ->get();

    if ($logs->isEmpty()) {
      return false;
    }

    return $logs->every(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED);
  }

  /**
   * Verifica si todos los pasos de migración de travesía están completados
   */
  public function checkAndUpdateCompletionStatus(ElectronicDocument $document, bool $isReversal = false): void
  {
    $allSteps = $isReversal
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER,
        VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL,
      ];

    $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->whereIn('step', $allSteps)
      ->get();

    if ($logs->isEmpty()) {
      return;
    }

    $allCompleted = $logs->every(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED);
    $anyFailed = $logs->contains(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED);

    if ($allCompleted) {
      $document->update([
        'traverse_migration_status' => $isReversal ? 'reverted' : 'completed',
        'traverse_migrated_at' => now(),
      ]);
    } elseif ($anyFailed) {
      $document->update(['traverse_migration_status' => 'failed']);
    } else {
      $document->update(['traverse_migration_status' => 'in_progress']);
    }
  }

  /**
   * Genera el siguiente número de asiento correlativo
   * Usa lockForUpdate para evitar race conditions
   */
  public function getNextAsientoNumber(): int
  {
    return DB::connection('dbtp')->transaction(function () {
      $max = DB::connection('dbtp')
        ->table('neInTbIntegracionAsientoCab')
        ->lockForUpdate()
        ->max('Asiento');

      return $max ? ($max + 1) : 1;
    }, 5); // 5 reintentos
  }
}