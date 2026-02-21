<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\TransferReception;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\Sede;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateProductReceptionToDynamicsJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 2;
  public int $timeout = 300;
  public int $backoff = 120;

  protected DatabaseSyncService $syncService;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $transferReceptionId
  ) {
    $this->onQueue('shipping_guides');
  }

  /**
   * Execute the job.
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    $this->syncService = $syncService;

    try {
      $this->processProductReception($this->transferReceptionId);
    } catch (Exception $e) {
      Log::error('Error en MigrateProductReceptionToDynamicsJob', [
        'transfer_reception_id' => $this->transferReceptionId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  /**
   * Procesa la migración de una recepción de productos
   */
  protected function processProductReception(int $receptionId): void
  {
    $reception = TransferReception::with([
      'transferMovement.warehouse',
      'transferMovement.warehouseDestination',
      'warehouse.sede',
      'shippingGuide',
      'details.product.articleClass'
    ])->find($receptionId);

    if (!$reception) {
      throw new Exception("Recepción de transferencia no encontrada. ID: {$receptionId}");
    }

    $shippingGuide = $reception->shippingGuide;
    if (!$shippingGuide) {
      throw new Exception("Guía de remisión no encontrada para la recepción. ID: {$receptionId}");
    }

    // Actualizar estado general a 'in_progress' si está pending
    if ($shippingGuide->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_PENDING) {
      $shippingGuide->update(['migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);
    }

    // Crear logs si no existen
    $this->ensureProductTransferLogsExist($shippingGuide, $reception);

    // 1. Verificar y sincronizar transferencia de inventario (Header)
    $this->verifyInventoryTransfer($shippingGuide, $reception);

    // 2. Verificar y sincronizar detalle de transferencia (Detail)
    $this->verifyInventoryTransferDetail($shippingGuide, $reception);

    // 3. Verificar y sincronizar serial de transferencia (Serial)
    $this->verifyInventoryTransferSerial($shippingGuide, $reception);

    // 4. Verificar si todo está completo
    $this->checkAndUpdateCompletionStatus($shippingGuide);
  }

  /**
   * Asegura que existan los logs para transferencias de productos
   */
  protected function ensureProductTransferLogsExist(ShippingGuides $shippingGuide, TransferReception $reception): void
  {
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    // Determinar los steps según si está cancelada o no
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

    // Tablas correspondientes
    $tables = [
      'neInTbTransferenciaInventario',
      'neInTbTransferenciaInventarioDet',
      'neInTbTransferenciaInventarioDtS',
    ];

    // Crear logs para cada step si no existen
    foreach ($steps as $index => $step) {
      $existingLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$existingLog) {
        // Construir el TransferenciaId para este step
        $transactionId = $this->buildTransferTransactionId($shippingGuide, $step);

        $this->getOrCreateLog(
          $shippingGuide->id,
          $step,
          $tables[$index],
          $transactionId,
          null // No es vehículo, no hay vehicle_id
        );
      }
    }
  }

  /**
   * Verifica el estado de la transferencia de inventario en la BD intermedia (Header)
   */
  protected function verifyInventoryTransfer(ShippingGuides $shippingGuide, TransferReception $reception): void
  {
    $transferLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER)
      ->first();

    if (!$transferLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Si no tiene dyn_series, necesita sincronizar
    if (empty($shippingGuide->dyn_series)) {
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncInventoryTransfer($shippingGuide, $reception, $isCancelled);
      return;
    }

    // Verificar si existe en la BD intermedia
    $existingTransfer = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    if (!$existingTransfer) {
      // NO EXISTE → SINCRONIZAR
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncInventoryTransfer($shippingGuide, $reception, $isCancelled);
      return;
    }

    $transferLog->updateProcesoEstado(
      $existingTransfer->ProcesoEstado ?? 0,
      $existingTransfer->ProcesoError ?? null
    );
  }

  /**
   * Verifica el estado del detalle de transferencia en la BD intermedia (Detail)
   */
  protected function verifyInventoryTransferDetail(ShippingGuides $shippingGuide, TransferReception $reception): void
  {
    $detailLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL)
      ->first();

    if (!$detailLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar si existe en la BD intermedia
    $existingDetail = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    if (!$existingDetail) {
      // NO EXISTE → SINCRONIZAR
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncInventoryTransferDetail($shippingGuide, $reception, $isCancelled);
      return;
    }

    $detailLog->updateProcesoEstado(1);
  }

  /**
   * Verifica el estado del serial de transferencia en la BD intermedia (Serial)
   */
  protected function verifyInventoryTransferSerial(ShippingGuides $shippingGuide, TransferReception $reception): void
  {
    $serialLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL)
      ->first();

    if (!$serialLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($serialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar si existe en la BD intermedia (verificamos por al menos un producto)
    $existingSerial = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDtS')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    if (!$existingSerial) {
      // NO EXISTE → SINCRONIZAR
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncInventoryTransferSerial($shippingGuide, $reception, $isCancelled);
      return;
    }

    $serialLog->updateProcesoEstado(1);
  }

  /**
   * Sincroniza la cabecera de transferencia de inventario (Header)
   */
  protected function syncInventoryTransfer(ShippingGuides $shippingGuide, TransferReception $reception, bool $isCancelled): void
  {
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER;

    $transferLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER],
      $shippingGuide->document_number,
      null
    );

    // Si ya está completado, no hacer nada
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar TransferenciaId con asterisco si está cancelada
      $transferId = 'PTRA-' . str_pad($shippingGuide->correlative, 8, '0', STR_PAD_LEFT);
      if ($isCancelled) {
        $transferId .= '*';
      }

      // Preparar datos para sincronización del header
      $data = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $transferId,
        'FechaEmision' => $reception->reception_date->format('Y-m-d'),
        'FechaContable' => $reception->reception_date->format('Y-m-d'),
        'Procesar' => 1,
        'ProcesoEstado' => 0,
        'ProcesoError' => '',
        'FechaProceso' => now()->format('Y-m-d H:i:s'),
      ];

      // Sincronizar cabecera de transferencia
      $transferLog->markAsInProgress();
      $this->syncService->sync('inventory_transfer', $data, 'create');
      $transferLog->updateProcesoEstado(0); // 0 = En proceso en la BD intermedia

      // Actualizar dyn_series en ShippingGuides con el TransferenciaId
      $shippingGuide->update([
        'dyn_series' => $transferId,
      ]);
    } catch (Exception $e) {
      Log::error('Error al sincronizar transferencia de inventario (productos)', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reception_id' => $reception->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      $transferLog->markAsFailed("Error al sincronizar transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el detalle de transferencia de inventario (Detail)
   * UNO POR UNO para cada producto
   */
  protected function syncInventoryTransferDetail(ShippingGuides $shippingGuide, TransferReception $reception, bool $isCancelled): void
  {
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL;

    $transferDetailLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL],
      $shippingGuide->document_number,
      null
    );

    // Si ya está completado, no hacer nada
    if ($transferDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar TransferenciaId con asterisco si está cancelada
      $transferId = 'PTRA-' . str_pad($shippingGuide->correlative, 8, '0', STR_PAD_LEFT);
      if ($isCancelled) {
        $transferId .= '*';
      }

      $transferOutMovement = $reception->transferMovement;
      $warehouseOrigin = $transferOutMovement->warehouse;
      $warehouseDestination = $transferOutMovement->warehouseDestination;

      if (!$warehouseOrigin || !$warehouseDestination) {
        throw new Exception("Almacenes de origen o destino no encontrados en el movimiento de transferencia.");
      }

      // Almacenes directamente del movimiento
      $warehouseOriginCode = $warehouseOrigin->dyn_code;
      $warehouseDestinationCode = $warehouseDestination->dyn_code;

      // Si está cancelada, invertir los almacenes
      $almacenIdIni = $isCancelled ? $warehouseDestinationCode : $warehouseOriginCode;
      $almacenIdFin = $isCancelled ? $warehouseOriginCode : $warehouseDestinationCode;

      $sede = $reception->warehouse->sede;
      if (!$sede) {
        throw new Exception("Sede del almacén de recepción no encontrada.");
      }

      // Sincronizar cada producto UNO POR UNO
      $lineNumber = 1;
      foreach ($reception->details as $detail) {
        if (!$detail->product) {
          Log::warning('Detalle de recepción sin producto asociado', [
            'transfer_reception_detail_id' => $detail->id
          ]);
          continue;
        }

        $product = $detail->product;

        // Cantidad total (recibida + observada)
        $quantity = $detail->quantity_received + $detail->observed_quantity;

        // Obtener almacenes para este producto específico según su article_class (para cuentas contables)
        $warehouseQuery = Warehouse::where('sede_id', $sede->id)
          ->where('type_operation_id', 804) // TIPO_OPERACION_POSTVENTA
          ->where('article_class_id', $product->ap_class_article_id)
          ->where('status', true);

        $warehouseStart = (clone $warehouseQuery)->where('is_received', false)->first();
        $warehouseEnd = (clone $warehouseQuery)->where('is_received', true)->first();

        if (!$warehouseStart || !$warehouseEnd) {
          throw new Exception("Almacenes no encontrados para el producto {$product->code} con clase de artículo {$product->ap_class_article_id}");
        }

        // Cuentas contables
        $inventoryAccount = $warehouseStart->inventory_account . '-' . $sede->dyn_code;
        $counterpartInventoryAccount = $warehouseEnd->inventory_account . '-' . $sede->dyn_code;

        if ($isCancelled) {
          $temp = $inventoryAccount;
          $inventoryAccount = $counterpartInventoryAccount;
          $counterpartInventoryAccount = $temp;
        }

        // Preparar datos para este producto
        $detailData = [
          'EmpresaId' => Company::AP_DYNAMICS,
          'TransferenciaId' => $transferId,
          'Linea' => $lineNumber,
          'ArticuloId' => $product->dyn_code ?? throw new Exception("El producto {$product->code} no tiene dyn_code"),
          'Motivo' => '',
          'UnidadMedidaId' => 'UND',
          'Cantidad' => $quantity,
          'AlmacenId_Ini' => $almacenIdIni,
          'AlmacenId_Fin' => $almacenIdFin,
          'CuentaInventario' => $inventoryAccount,
          'CuentaContrapartida' => $counterpartInventoryAccount,
        ];

        // Sincronizar detalle
        $transferDetailLog->markAsInProgress();
        $this->syncService->sync('inventory_transfer_dt', $detailData, 'create');

        $lineNumber++;
      }

      $transferDetailLog->updateProcesoEstado(0);

    } catch (Exception $e) {
      Log::error('Error al sincronizar detalle de transferencia de inventario (productos)', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reception_id' => $reception->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      $transferDetailLog->markAsFailed("Error al sincronizar detalle de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el serial de transferencia de inventario (Serial)
   * UNO POR UNO para cada producto usando su dyn_code
   */
  protected function syncInventoryTransferSerial(ShippingGuides $shippingGuide, TransferReception $reception, bool $isCancelled): void
  {
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL;

    $transferSerialLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL],
      $shippingGuide->document_number,
      null
    );

    // Si ya está completado, no hacer nada
    if ($transferSerialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar TransferenciaId con asterisco si está cancelada
      $transferId = 'PTRA-' . str_pad($shippingGuide->correlative, 8, '0', STR_PAD_LEFT);
      if ($isCancelled) {
        $transferId .= '*';
      }

      // Sincronizar cada producto UNO POR UNO
      $lineNumber = 1;
      foreach ($reception->details as $detail) {
        if (!$detail->product) {
          Log::warning('Detalle de recepción sin producto asociado', [
            'transfer_reception_detail_id' => $detail->id
          ]);
          continue;
        }

        $product = $detail->product;

        // Preparar datos para sincronización del serial
        $serialData = [
          'EmpresaId' => Company::AP_DYNAMICS,
          'TransferenciaId' => $transferId,
          'Linea' => $lineNumber,
          'Serie' => $product->dyn_code ?? throw new Exception("El producto {$product->code} no tiene dyn_code"),
          'ArticuloId' => $product->dyn_code,
          'DatoUsuario1' => $product->dyn_code,
          'DatoUsuario2' => $product->dyn_code,
        ];

        // Sincronizar serial
        $transferSerialLog->markAsInProgress();
        $this->syncService->sync('inventory_transfer_dts', $serialData, 'create');

        $lineNumber++;
      }

      $transferSerialLog->updateProcesoEstado(0);

    } catch (Exception $e) {
      Log::error('Error al sincronizar serial de transferencia de inventario (productos)', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reception_id' => $reception->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      $transferSerialLog->markAsFailed("Error al sincronizar serial de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Construye el TransferenciaId para guías de transferencia de productos
   */
  protected function buildTransferTransactionId(ShippingGuides $shippingGuide, string $step): string
  {
    // Si ya tiene dyn_series, usarlo directamente
    if (!empty($shippingGuide->dyn_series)) {
      $transactionId = $shippingGuide->dyn_series;
    } else {
      // Productos siempre usan prefijo PTRA-
      $transactionId = 'PTRA-' . str_pad($shippingGuide->correlative, 8, '0', STR_PAD_LEFT);
    }

    // Si es una reversión, agregar asterisco
    if (str_contains($step, 'REVERSAL')) {
      $transactionId .= '*';
    }

    return $transactionId;
  }

  /**
   * Verifica si todos los pasos están completos y actualiza el estado general
   */
  protected function checkAndUpdateCompletionStatus(ShippingGuides $shippingGuide): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)->get();

    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
        $log->proceso_estado === 1;
    });

    $hasFailed = $logs->contains(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED;
    });

    if ($allCompleted && $logs->count() === 3) { // 3 pasos en total
      // Marcar la guía como sincronizada
      $shippingGuide->update([
        'status_dynamic' => 1,
        'migration_status' => 'completed',
        'migrated_at' => now(),
      ]);
    } elseif ($hasFailed) {
      $shippingGuide->update([
        'status_dynamic' => 0,
        'migration_status' => 'failed',
      ]);
    }
  }

  /**
   * Obtiene o crea un registro de log
   */
  protected function getOrCreateLog(int $shippingGuideId, string $step, string $tableName, ?string $externalId = null, ?int $vehicleId = null): VehiclePurchaseOrderMigrationLog
  {
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

  public function failed(\Throwable $exception): void
  {
    Log::error('MigrateProductReceptionToDynamicsJob falló completamente', [
      'transfer_reception_id' => $this->transferReceptionId,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString()
    ]);
  }
}