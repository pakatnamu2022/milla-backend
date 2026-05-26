<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\gp\gestionsistema\Company;
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
    public int $shippingGuideId
  )
  {
    $this->onQueue('shipping_guides');
  }

  /**
   * Execute the job.
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    $this->syncService = $syncService;

    try {
      $this->processProductTransfer($this->shippingGuideId);
    } catch (Exception $e) {
      Log::error('Error en MigrateProductReceptionToDynamicsJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  /**
   * Procesa la migración de una transferencia de productos (sin necesidad de recepción física)
   */
  protected function processProductTransfer(int $shippingGuideId): void
  {
    $shippingGuide = ShippingGuides::with([
      'inventoryMovement.warehouse',
      'inventoryMovement.warehouseDestination',
      'inventoryMovement.details.product.articleClass'
    ])->find($shippingGuideId);

    if (!$shippingGuide) {
      throw new Exception("Guía de remisión no encontrada. ID: {$shippingGuideId}");
    }

    $transferOutMovement = $shippingGuide->inventoryMovement;
    if (!$transferOutMovement) {
      throw new Exception("Movimiento de inventario no encontrado para la guía. ID: {$shippingGuideId}");
    }

    if ($transferOutMovement->movement_type !== InventoryMovement::TYPE_TRANSFER_OUT) {
      throw new Exception("El movimiento asociado no es de tipo TRANSFER_OUT. Tipo: {$transferOutMovement->movement_type}");
    }

    // Actualizar estado general a 'in_progress' si está pending
    if ($shippingGuide->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_PENDING) {
      $shippingGuide->update(['migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);
    }

    // 1. Verificar y sincronizar artículos (productos) PRIMERO
    $this->verifyAndSyncProducts($shippingGuide, $transferOutMovement);

    // 2. Crear logs de transferencia (solo después de que productos estén en proceso)
    $this->ensureProductTransferLogsExist($shippingGuide);

    // 3. Verificar y sincronizar detalle de transferencia (Detail)
    $this->verifyInventoryTransferDetail($shippingGuide, $transferOutMovement);

    // 4. Verificar y sincronizar transferencia de inventario (Header)
    $this->verifyInventoryTransfer($shippingGuide, $transferOutMovement);

    // 5. Verificar si todo está completo
    $this->checkAndUpdateCompletionStatus($shippingGuide);
  }

  /**
   * Verifica y sincroniza los artículos (productos) de la transferencia
   */
  protected function verifyAndSyncProducts(ShippingGuides $shippingGuide, InventoryMovement $transferOutMovement): void
  {
    // Obtener items con productos del movimiento de salida
    $items = $transferOutMovement->details()->with('product')->get();

    if ($items->isEmpty()) {
      return;
    }

    // Obtener productos únicos con dyn_code válido
    $uniqueProducts = $items->filter(function ($detail) {
      return $detail->product && $detail->product->dyn_code;
    })->pluck('product')->unique('id');

    if ($uniqueProducts->isEmpty()) {
      return;
    }

    // Crear logs y despachar jobs para cada producto
    foreach ($uniqueProducts as $product) {
      // Crear log para este producto
      $articleLog = $this->getOrCreateArticleLog(
        $shippingGuide->id,
        VehiclePurchaseOrderMigrationLog::STEP_ARTICLE,
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_ARTICLE],
        $product->dyn_code
      );

      // Si ya está completado, continuar
      if ($articleLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED && $articleLog->proceso_estado === 1) {
        continue;
      }

      // Verificar en la BD intermedia
      $existingArticle = DB::connection('dbtp')
        ->table('neInTbArticulo')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('Articulo', $product->dyn_code)
        ->first();

      if (!$existingArticle) {
        // No existe, despachar job para sincronizar el producto
        try {
          $articleLog->markAsInProgress();
          SyncProductArticleJob::dispatch($product->id);
        } catch (Exception $e) {
          $articleLog->markAsFailed("Error al despachar job de artículo producto: {$e->getMessage()}");
        }
      } else {
        // Existe, actualizar el estado del log
        $articleLog->updateProcesoEstado(
          $existingArticle->ProcesoEstado ?? 0,
          $existingArticle->ProcesoError ?? null
        );
      }
    }
  }

  /**
   * Asegura que existan los logs para transferencias de productos
   */
  protected function ensureProductTransferLogsExist(ShippingGuides $shippingGuide): void
  {
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    // Determinar los steps según si está cancelada o no
    $steps = $isCancelled
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL,
      ];

    // Crear logs para cada step si no existen
    foreach ($steps as $step) {
      $tableName = VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[$step] ?? null;

      if (!$tableName) {
        throw new Exception("No se encontró tabla mapeada para el step: {$step}");
      }

      $existingLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$existingLog) {
        // Construir el TransferenciaId para este step
        $isReversal = str_contains($step, 'REVERSAL');
        $transactionId = $shippingGuide->getDynamicsTransferTransactionId($isReversal);

        $this->getOrCreateLog(
          $shippingGuide->id,
          $step,
          VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[$step],
          $transactionId,
          null // No es vehículo, no hay vehicle_id
        );
      }
    }
  }

  /**
   * Verifica el estado de la transferencia de inventario en la BD intermedia (Header)
   */
  protected function verifyInventoryTransfer(ShippingGuides $shippingGuide, InventoryMovement $transferOutMovement): void
  {
    // Determinar si está cancelada PRIMERO para buscar el step correcto
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    // Buscar el step correcto según si está cancelada o no
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER;

    $transferLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', $step)
      ->first();

    if (!$transferLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar que TODOS los productos (artículos) estén procesados antes de continuar
    $productLogs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
      ->get();

    // Si hay productos, verificar que todos estén procesados (proceso_estado = 1)
    if ($productLogs->isNotEmpty()) {
      $pendingProducts = $productLogs->filter(function ($log) {
        return $log->proceso_estado !== 1;
      });

      if ($pendingProducts->isNotEmpty()) {
        // Hay productos pendientes, no se puede sincronizar la transferencia aún
        return;
      }
    }

    // Construir el TransferenciaId para este step (con asterisco si está cancelada)
    $transactionId = $shippingGuide->getDynamicsTransferTransactionId($isCancelled);

    // Verificar si existe en la BD intermedia
    $existingTransfer = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $transactionId)
      ->first();

    if (!$existingTransfer) {
      // NO EXISTE → SINCRONIZAR
      $this->syncInventoryTransfer($shippingGuide, $transferOutMovement, $isCancelled);
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
  protected function verifyInventoryTransferDetail(ShippingGuides $shippingGuide, InventoryMovement $transferOutMovement): void
  {
    // Determinar si está cancelada PRIMERO para buscar el step correcto
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    // Buscar el step correcto según si está cancelada o no
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL;

    $detailLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', $step)
      ->first();

    if (!$detailLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    $productLogs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id',
      $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
      ->get();

    // Si hay productos, verificar que todos estén procesados (proceso_estado = 1)
    if ($productLogs->isNotEmpty()) {
      $pendingProducts = $productLogs->filter(function ($log) {
        return $log->proceso_estado !== 1;
      });

      if ($pendingProducts->isNotEmpty()) {
        // Hay productos pendientes, no se puede sincronizar el detalle aún
        return;
      }
    }

    // Contar cuántos detalles deberían existir (del movimiento de salida)
    $expectedDetailsCount = $transferOutMovement->details->filter(function ($detail) {
      return $detail->product && $detail->product->dyn_code;
    })->count();

    if ($expectedDetailsCount === 0) {
      return;
    }

    // Construir el TransferenciaId para este step (con asterisco si está cancelada)
    $transactionId = $shippingGuide->getDynamicsTransferTransactionId($isCancelled);

    // Verificar cuántos detalles existen en la BD intermedia
    $existingDetailsCount = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $transactionId)
      ->count();

    if ($existingDetailsCount < $expectedDetailsCount) {
      $this->syncInventoryTransferDetail($shippingGuide, $transferOutMovement, $isCancelled);
      return;
    }

    // Todos los detalles existen, actualizar estado
    $detailLog->updateProcesoEstado(1);
  }

  /**
   * Sincroniza la cabecera de transferencia de inventario (Header)
   */
  protected function syncInventoryTransfer(ShippingGuides $shippingGuide, InventoryMovement $transferOutMovement, bool $isCancelled): void
  {
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER;

    $transferLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[$step],
      $shippingGuide->document_number,
      null
    );

    // Si ya está completado, no hacer nada
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar TransferenciaId con asterisco si está cancelada
      $transferId = $shippingGuide->getDynamicsTransferTransactionId($isCancelled);

      // Preparar datos para sincronización del header
      $data = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $transferId,
        'FechaEmision' => $shippingGuide->created_at->format('Y-m-d'),
        'FechaContable' => $shippingGuide->created_at->format('Y-m-d'),
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
        'transfer_out_movement_id' => $transferOutMovement->id,
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
  protected function syncInventoryTransferDetail(ShippingGuides $shippingGuide, InventoryMovement $transferOutMovement, bool $isCancelled): void
  {
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL;

    $transferDetailLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[$step],
      $shippingGuide->document_number,
      null
    );

    // Si ya está completado, no hacer nada
    if ($transferDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar TransferenciaId con asterisco si está cancelada
      $transferId = $shippingGuide->getDynamicsTransferTransactionId($isCancelled);

      $warehouseOrigin = $transferOutMovement->warehouse;
      $warehouseDestination = $transferOutMovement->warehouseDestination;

      if (!$warehouseOrigin || !$warehouseDestination) {
        throw new Exception("Almacenes de origen o destino no encontrados en el movimiento de transferencia.");
      }

      // Almacenes directamente del movimiento
      // IMPORTANTE: Si está cancelada, el movimiento YA viene con los almacenes invertidos
      // desde InventoryMovementService->cancelTransfer(), no los invertimos nuevamente
      $warehouseOriginCode = $warehouseOrigin->dyn_code;
      $warehouseDestinationCode = $warehouseDestination->dyn_code;

      $almacenIdIni = $warehouseOriginCode;
      $almacenIdFin = $warehouseDestinationCode;

      $sedeOrigin = $warehouseOrigin->sede;
      $sedeDestination = $warehouseDestination->sede;

      if (!$sedeDestination) {
        throw new Exception("Sede del almacén de destino no encontrada.");
      }

      // Sincronizar cada producto UNO POR UNO usando los detalles del movimiento de salida
      $lineNumber = 1;
      foreach ($transferOutMovement->details as $detail) {
        if (!$detail->product) {
          Log::warning('Detalle de movimiento sin producto asociado', [
            'inventory_movement_detail_id' => $detail->id
          ]);
          continue;
        }

        $product = $detail->product;

        // Verificar si esta línea ya existe en la BD intermedia
        $existingLine = DB::connection('dbtp')
          ->table('neInTbTransferenciaInventarioDet')
          ->where('EmpresaId', Company::AP_DYNAMICS)
          ->where('TransferenciaId', $transferId)
          ->where('Linea', $lineNumber)
          ->first();

        if ($existingLine) {
          $lineNumber++;
          continue;
        }

        // Cantidad del movimiento de salida (cantidad enviada/fiscal)
        $quantity = $detail->quantity;

        // Obtener almacenes para este producto específico según su article_class (para cuentas contables)
        $warehouseQuery = Warehouse::where('sede_id', $sedeDestination->id)
          ->where('type_operation_id', ApMasters::TIPO_OPERACION_POSTVENTA) // TIPO_OPERACION_POSTVENTA
          ->where('article_class_id', $product->ap_class_article_id)
          ->where('status', true);

        $warehouseStart = (clone $warehouseQuery)->where('is_received', true)->first();
        $warehouseEnd = (clone $warehouseQuery)->where('is_received', true)->first();

        if (!$warehouseStart || !$warehouseEnd) {
          throw new Exception("Almacenes no encontrados para el producto {$product->code} con clase de artículo {$product->ap_class_article_id}");
        }

        // Cuentas contables
        // IMPORTANTE: Si está cancelada, los almacenes ya están invertidos en el movimiento,
        // por lo que $sedeOrigin y $sedeDestination ya reflejan la inversión correctamente
        $inventoryAccount = $warehouseStart->inventory_account . '-' . $sedeOrigin->dyn_code;
        $counterpartInventoryAccount = $warehouseEnd->inventory_account . '-' . $sedeDestination->dyn_code;

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
        'transfer_out_movement_id' => $transferOutMovement->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      $transferDetailLog->markAsFailed("Error al sincronizar detalle de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Verifica si todos los pasos están completos y actualiza el estado general
   */
  protected function checkAndUpdateCompletionStatus(ShippingGuides $shippingGuide): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)->get();

    // Verificar que TODOS los logs estén completados con proceso_estado = 1
    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
        $log->proceso_estado === 1;
    });

    $hasFailed = $logs->contains(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED;
    });

    // Determinar si está cancelada para buscar los steps correctos
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    // Los pasos base son 2: transferencia + detalle de transferencia
    // Pero también puede haber N logs de artículos (productos)
    $baseSteps = $isCancelled
      ? [
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL,
      ]
      : [
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER,
        VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL,
      ];

    // Verificar que existan los pasos base
    $hasBaseSteps = true;
    foreach ($baseSteps as $step) {
      if (!$logs->where('step', $step)->first()) {
        $hasBaseSteps = false;
        break;
      }
    }

    if ($allCompleted && $hasBaseSteps) {
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

  /**
   * Obtiene o crea un registro de log para artículos
   * Permite múltiples logs del mismo step con diferentes external_id (productos)
   */
  protected function getOrCreateArticleLog(int $shippingGuideId, string $step, string $tableName, ?string $externalId = null): VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'shipping_guide_id' => $shippingGuideId,
        'step' => $step,
        'external_id' => $externalId, // Incluir en búsqueda para productos
      ],
      [
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'table_name' => $tableName,
      ]
    );
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('MigrateProductReceptionToDynamicsJob falló completamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString()
    ]);
  }
}