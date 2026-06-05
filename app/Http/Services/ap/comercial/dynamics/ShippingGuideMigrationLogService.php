<?php

namespace App\Http\Services\ap\comercial\dynamics;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Facades\Log;

class ShippingGuideMigrationLogService
{
  public function getOrCreateLog(
    int $shippingGuideId,
    string $step,
    string $tableName,
    ?string $externalId = null,
    ?int $vehicleId = null
  ): VehiclePurchaseOrderMigrationLog {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'shipping_guide_id' => $shippingGuideId,
        'step' => $step,
      ],
      [
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'table_name' => $tableName,
        'external_id' => $externalId,
        'ap_vehicles_id' => $vehicleId,
      ]
    );
  }

  public function ensureSaleLogsExist(ShippingGuides $shippingGuide): void
  {
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    $steps = $isCancelled
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE,
        VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL,
      ];

    $tables = [
      'neInTbTransaccionInventario',
      'neInTbTransaccionInventarioDet',
      'neInTbTransaccionInventarioDtS',
    ];

    foreach ($steps as $index => $step) {
      $existingLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$existingLog) {
        $transactionId = $this->buildSaleTransactionId($shippingGuide, $step);
        $this->getOrCreateLog(
          $shippingGuide->id,
          $step,
          $tables[$index],
          $transactionId,
          $shippingGuide->vehicleMovement?->vehicle?->id
        );
      }
    }
  }

  public function ensureTransferLogsExist(ShippingGuides $shippingGuide): void
  {
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    $steps = $isCancelled
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL,
      ];

    $tables = [
      'neInTbTransferenciaInventario',
      'neInTbTransferenciaInventarioDet',
      'neInTbTransferenciaInventarioDtS',
    ];

    foreach ($steps as $index => $step) {
      $existingLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$existingLog) {
        $transactionId = $this->buildTransferTransactionId($shippingGuide, $step);
        $this->getOrCreateLog(
          $shippingGuide->id,
          $step,
          $tables[$index],
          $transactionId,
          $shippingGuide->vehicleMovement?->vehicle?->id
        );
      }
    }
  }

  public function checkAndUpdateCompletionStatus(ShippingGuides $shippingGuide): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)->get();

    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
        $log->proceso_estado === 1;
    });

    $hasFailed = $logs->contains(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED;
    });

    if ($allCompleted && $logs->count() === 3) {
      $shippingGuide->update([
        'status_dynamic' => 1,
        'migration_status' => 'completed',
        'migrated_at' => now(),
      ]);
    } elseif ($hasFailed) {
      Log::error('Migración de guía de remisión fallida', [
        'shipping_guide_id' => $shippingGuide->id,
        'document_number' => $shippingGuide->document_number,
      ]);
      $shippingGuide->update([
        'status_dynamic' => 0,
        'migration_status' => 'failed',
      ]);
    }
  }

  public function buildSaleTransactionId(ShippingGuides $shippingGuide, string $step): string
  {
    if (!empty($shippingGuide->dyn_series)) {
      $transactionId = $shippingGuide->dyn_series;
    } else {
      $prefix = $shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA ? 'CV-' : 'CS-';
      $transactionId = $prefix . $shippingGuide->document_number;
    }

    if (str_contains($step, 'REVERSAL')) {
      $transactionId .= '*';
    }

    return $transactionId;
  }

  public function buildTransferTransactionId(ShippingGuides $shippingGuide, string $step): string
  {
    if (!empty($shippingGuide->dyn_series)) {
      $transactionId = $shippingGuide->dyn_series;
    } else {
      $prefix = $shippingGuide->getTransferPrefix($shippingGuide);
      $transactionId = $prefix . $shippingGuide->document_number;
    }

    if (str_contains($step, 'REVERSAL')) {
      $transactionId .= '*';
    }

    return $transactionId;
  }
}
