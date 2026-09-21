<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\TraverseAccountingEntryDetailResource;
use App\Http\Resources\Dynamics\TraverseAccountingEntryHeaderResource;
use App\Http\Resources\Dynamics\TraverseAdjustmentDetailResource;
use App\Http\Resources\Dynamics\TraverseAdjustmentHeaderResource;
use App\Http\Services\ap\facturacion\AccountingEntryService;
use App\Http\Services\Billing\TraverseMigrationLogService;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\LinkPurchaseSaleTransaction;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Migra productos en travesía asociados a un comprobante de venta hacia Microsoft Dynamics 365.
 *
 * FLUJO GENERAL:
 *   1. Valida que las compras asociadas estén migradas y contabilizadas.
 *   2. Ejecuta PASO 1: Ajuste de inventario (salida/ingreso).
 *   3. Espera a que el PASO 1 esté completado (ProcesoEstado = 1).
 *   4. Ejecuta PASO 2: Asiento contable.
 *   5. Espera a que el PASO 2 esté completado.
 *   6. Marca el documento con traverse_migration_status = completed.
 *
 * PUEDE DESPACHARSE:
 *   - Con un $electronicDocumentId específico → procesa solo ese documento.
 *   - Con $isReversal = true → ejecuta la reversión del proceso.
 *
 * COLA: traverse_migration | tries: 3 | timeout: 300 s | backoff: 120 s
 */
class VerifyAndMigrateTraverseJob implements ShouldQueue
{
  use Queueable;

  const QUEUE_DEFAULT = 'traverse_migration';

  public int $tries = 3;
  public int $timeout = 300;
  public int $backoff = 120;

  public function __construct(
    public int $electronicDocumentId,
    public bool $isReversal = false,
    string $queue = self::QUEUE_DEFAULT
  )
  {
    $this->onQueue($queue);
  }

  /**
   * @throws Exception
   */
  public function handle(
    DatabaseSyncService $syncService,
    TraverseMigrationLogService $logService,
    AccountingEntryService $accountingService
  ): void
  {
    try {
      Log::info('Iniciando migración de travesía', [
        'electronic_document_id' => $this->electronicDocumentId,
        'is_reversal' => $this->isReversal,
      ]);

      // Cargar el documento con sus relaciones necesarias
      $document = ElectronicDocument::with([
        'items.linkTransactions.purchaseOrderItem.purchaseOrder',
        'items.product.classArticle',
      ])->find($this->electronicDocumentId);

      if (!$document) {
        Log::warning('Documento electrónico no encontrado', [
          'electronic_document_id' => $this->electronicDocumentId,
        ]);
        return;
      }

      // Validar que tenga productos en travesía
      if (!$document->has_product_traverse) {
        Log::info('Documento no tiene productos en travesía habilitados', [
          'electronic_document_id' => $document->id,
        ]);
        return;
      }

      // Validar que esté asociado
      if (!$document->associate_purchase_traverse && !$this->isReversal) {
        Log::info('Documento no tiene travesía asociada', [
          'electronic_document_id' => $document->id,
        ]);
        return;
      }

      // Validar que las compras estén migradas y contabilizadas
      if (!$this->isReversal) {
        $this->validatePurchaseOrders($document);
      }

      // Actualizar estado a in_progress si está pendiente
      if ($document->traverse_migration_status === null || $document->traverse_migration_status === 'pending') {
        $document->update(['traverse_migration_status' => 'in_progress']);
      }

      // PASO 1: Procesar ajuste de inventario
      $this->processInventoryAdjustment($document, $syncService, $logService);

      // Verificar si el ajuste está completado antes de continuar
      if (!$logService->checkAdjustmentCompletionStatus($document, $this->isReversal)) {
        Log::info('Ajuste de inventario aún no completado, esperando ProcesoEstado = 1', [
          'electronic_document_id' => $document->id,
        ]);
        return;
      }

      // PASO 2: Procesar asiento contable (solo si el ajuste está completado)
      $this->processAccountingEntry($document, $syncService, $logService, $accountingService);

      // Verificar si todos los pasos están completados
      $logService->checkAndUpdateCompletionStatus($document, $this->isReversal);

      Log::info('Migración de travesía completada exitosamente', [
        'electronic_document_id' => $document->id,
        'is_reversal' => $this->isReversal,
      ]);

    } catch (Exception $e) {
      Log::error('Error en VerifyAndMigrateTraverseJob', [
        'electronic_document_id' => $this->electronicDocumentId,
        'is_reversal' => $this->isReversal,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      // Actualizar estado del documento
      if (isset($document)) {
        $logService->checkAndUpdateCompletionStatus($document, $this->isReversal);
      }

      throw $e;
    }
  }

  /**
   * Valida que las compras asociadas estén migradas y contabilizadas
   */
  protected function validatePurchaseOrders(ElectronicDocument $document): void
  {
    $purchaseOrderIds = LinkPurchaseSaleTransaction::where('status', 'active')
      ->whereHas('electronicDocumentItem', function ($query) use ($document) {
        $query->where('ap_billing_electronic_document_id', $document->id);
      })
      ->with('purchaseOrderItem.purchaseOrder')
      ->get()
      ->pluck('purchaseOrderItem.purchaseOrder.id')
      ->unique();

    foreach ($purchaseOrderIds as $purchaseOrderId) {
      $purchaseOrder = \App\Models\ap\compras\PurchaseOrder::find($purchaseOrderId);

      if (!$purchaseOrder) {
        throw new Exception("Orden de compra no encontrada (ID: {$purchaseOrderId}).");
      }

      if ($purchaseOrder->status != 1) {
        throw new Exception("La orden de compra {$purchaseOrder->number} no está activa (status debe ser 1).");
      }

      if (!in_array($purchaseOrder->migration_status, ['completed', 'updated_with_nc'])) {
        throw new Exception(
          "La orden de compra {$purchaseOrder->number} no tiene un migration_status válido " .
          "(debe ser 'completed' o 'updated_with_nc'). Actual: {$purchaseOrder->migration_status}"
        );
      }

      if (empty($purchaseOrder->invoice_dynamics)) {
        throw new Exception("La orden de compra {$purchaseOrder->number} no tiene invoice_dynamics.");
      }

      if (empty($purchaseOrder->receipt_dynamics)) {
        throw new Exception("La orden de compra {$purchaseOrder->number} no tiene receipt_dynamics.");
      }
    }

    Log::info('Validación de órdenes de compra completada', [
      'electronic_document_id' => $document->id,
      'purchase_orders_validated' => $purchaseOrderIds->count(),
    ]);
  }

  /**
   * Procesa el ajuste de inventario (PASO 1)
   */
  protected function processInventoryAdjustment(
    ElectronicDocument $document,
    DatabaseSyncService $syncService,
    TraverseMigrationLogService $logService
  ): void
  {
    // Asegurar que existan los logs
    $logService->ensureTraverseAdjustmentLogsExist($document, $this->isReversal);

    $headerStep = $this->isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT;

    $detailStep = $this->isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ADJUSTMENT_DETAIL;

    // Verificar y sincronizar cabecera
    $this->verifyAndSyncAdjustmentHeader($document, $headerStep, $syncService, $logService);

    // Verificar y sincronizar detalle
    $this->verifyAndSyncAdjustmentDetail($document, $detailStep, $syncService, $logService);
  }

  /**
   * Verifica y sincroniza la cabecera del ajuste
   */
  protected function verifyAndSyncAdjustmentHeader(
    ElectronicDocument $document,
    string $step,
    DatabaseSyncService $syncService,
    TraverseMigrationLogService $logService
  ): void
  {
    $headerLog = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->where('step', $step)
      ->first();

    if (!$headerLog) {
      return;
    }

    if ($headerLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    if ($logService->hasExceededAttemptLimit($headerLog)) {
      if ($headerLog->status !== VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
        $headerLog->markAsFailed('Máximo de intentos alcanzado. Requiere intervención manual.');
      }
      return;
    }

    $transactionId = $logService->buildTraverseTransactionId($document, $this->isReversal);

    // Verificar si ya existe en Dynamics
    $existingHeader = DB::connection('dbtp')
      ->table('neInTbTransaccionInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransaccionId', $transactionId)
      ->first();

    if (!$existingHeader) {
      // No existe, sincronizar
      $headerLog->markAsInProgress();

      $resource = new TraverseAdjustmentHeaderResource($document, $this->isReversal);
      $data = $resource->toArray(request());
      $syncService->sync('inventory_transaction', $data, 'create');

      // Guardar el TransaccionId en link_purchase_sale_transactions
      LinkPurchaseSaleTransaction::whereHas('electronicDocumentItem', function ($query) use ($document) {
        $query->where('ap_billing_electronic_document_id', $document->id);
      })->where('status', 'active')
        ->update(['dyn_transaction_id' => $transactionId]);

      $headerLog->updateProcesoEstado(0);

      Log::info('Cabecera de ajuste sincronizada', [
        'electronic_document_id' => $document->id,
        'transaction_id' => $transactionId,
      ]);
    } else {
      // Ya existe, actualizar ProcesoEstado
      $headerLog->updateProcesoEstado(
        $existingHeader->ProcesoEstado ?? 0,
        $existingHeader->ProcesoError ?? null,
        true
      );
    }
  }

  /**
   * Verifica y sincroniza el detalle del ajuste
   */
  protected function verifyAndSyncAdjustmentDetail(
    ElectronicDocument $document,
    string $step,
    DatabaseSyncService $syncService,
    TraverseMigrationLogService $logService
  ): void
  {
    $detailLog = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->where('step', $step)
      ->first();

    if (!$detailLog) {
      return;
    }

    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    if ($logService->hasExceededAttemptLimit($detailLog)) {
      if ($detailLog->status !== VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
        $detailLog->markAsFailed('Máximo de intentos alcanzado. Requiere intervención manual.');
      }
      return;
    }

    $transactionId = $logService->buildTraverseTransactionId($document, $this->isReversal);

    // Verificar si ya existe el detalle en Dynamics
    $existingDetail = DB::connection('dbtp')
      ->table('neInTbTransaccionInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransaccionId', $transactionId)
      ->first();

    if (!$existingDetail) {
      // No existe, sincronizar
      $resource = new TraverseAdjustmentDetailResource($document, $this->isReversal);
      $details = $resource->toArray(request());

      $detailLog->markAsInProgress();

      foreach ($details as $detail) {
        $syncService->sync('inventory_transaction_dt', $detail, 'create');
      }

      $detailLog->updateProcesoEstado(0);

      Log::info('Detalle de ajuste sincronizado', [
        'electronic_document_id' => $document->id,
        'transaction_id' => $transactionId,
        'lines' => count($details),
      ]);
    } else {
      // Ya existe, marcar como completado
      $detailLog->updateProcesoEstado(1);
    }
  }

  /**
   * Procesa el asiento contable (PASO 2)
   */
  protected function processAccountingEntry(
    ElectronicDocument $document,
    DatabaseSyncService $syncService,
    TraverseMigrationLogService $logService,
    AccountingEntryService $accountingService
  ): void
  {
    // Asegurar que existan los logs
    $logService->ensureTraverseAccountingLogsExist($document, $this->isReversal);

    $headerStep = $this->isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_HEADER;

    $detailStep = $this->isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_TRAVERSE_ACCOUNTING_ENTRY_DETAIL;

    // Verificar y sincronizar cabecera del asiento
    $this->verifyAndSyncAccountingHeader($document, $headerStep, $syncService, $logService, $accountingService);

    // Verificar y sincronizar detalle del asiento
    $this->verifyAndSyncAccountingDetail($document, $detailStep, $syncService, $logService);
  }

  /**
   * Verifica y sincroniza la cabecera del asiento contable
   */
  protected function verifyAndSyncAccountingHeader(
    ElectronicDocument $document,
    string $step,
    DatabaseSyncService $syncService,
    TraverseMigrationLogService $logService,
    AccountingEntryService $accountingService
  ): void
  {
    $headerLog = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->where('step', $step)
      ->first();

    if (!$headerLog) {
      return;
    }

    if ($headerLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    if ($logService->hasExceededAttemptLimit($headerLog)) {
      if ($headerLog->status !== VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
        $headerLog->markAsFailed('Máximo de intentos alcanzado. Requiere intervención manual.');
      }
      return;
    }

    $referencia = $logService->buildTraverseReferencia($document, $this->isReversal);

    // Verificar si ya existe en Dynamics
    $existingHeader = DB::connection('dbtp')
      ->table('neInTbIntegracionAsientoCab')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('Referencia', $referencia)
      ->first();

    if ($existingHeader) {
      // Ya existe, solo actualizar ProcesoEstado
      $headerLog->updateProcesoEstado(
        $existingHeader->ProcesoEstado ?? 0,
        $existingHeader->ProcesoError ?? null,
        true
      );

      Log::info('Cabecera de asiento ya existe en Dynamics', [
        'electronic_document_id' => $document->id,
        'referencia' => $referencia,
      ]);
      return;
    }

    // Generar número de asiento con lock distribuido
    $asientoNumber = null;
    $lock = Cache::lock('sync_accounting_entry_asiento', 30);

    try {
      $lock->block(20);

      $asientoNumber = $accountingService->getNextAsientoNumber();

      Log::info('Número de asiento generado para travesía', [
        'electronic_document_id' => $document->id,
        'asiento_number' => $asientoNumber,
      ]);

      $headerLog->markAsInProgress();

      $resource = new TraverseAccountingEntryHeaderResource($document, $asientoNumber, $this->isReversal);
      $data = $resource->toArray(request());

      $syncService->sync('accounting_entry_header', $data, 'create');

      // Guardar el número de asiento en link_purchase_sale_transactions
      LinkPurchaseSaleTransaction::whereHas('electronicDocumentItem', function ($query) use ($document) {
        $query->where('ap_billing_electronic_document_id', $document->id);
      })->where('status', 'active')
        ->update(['dyn_asiento_number' => $asientoNumber]);

      $headerLog->updateProcesoEstado(0);

    } finally {
      $lock->forceRelease();
    }

    Log::info('Cabecera de asiento contable sincronizada', [
      'electronic_document_id' => $document->id,
      'asiento_number' => $asientoNumber,
    ]);
  }

  /**
   * Verifica y sincroniza el detalle del asiento contable
   */
  protected function verifyAndSyncAccountingDetail(
    ElectronicDocument $document,
    string $step,
    DatabaseSyncService $syncService,
    TraverseMigrationLogService $logService
  ): void
  {
    $detailLog = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->where('step', $step)
      ->first();

    if (!$detailLog) {
      return;
    }

    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    if ($logService->hasExceededAttemptLimit($detailLog)) {
      if ($detailLog->status !== VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
        $detailLog->markAsFailed('Máximo de intentos alcanzado. Requiere intervención manual.');
      }
      return;
    }

    $referencia = $logService->buildTraverseReferencia($document, $this->isReversal);

    // Verificar si ya existe el detalle
    $existingDetail = DB::connection('dbtp')
      ->table('neInTbIntegracionAsientoDet')
      ->where('Referencia', $referencia)
      ->first();

    if ($existingDetail) {
      // Ya existe, marcar como completado
      $detailLog->updateProcesoEstado(1);
      return;
    }

    // Obtener el número de asiento desde link_purchase_sale_transactions
    $asientoNumber = LinkPurchaseSaleTransaction::whereHas('electronicDocumentItem', function ($query) use ($document) {
      $query->where('ap_billing_electronic_document_id', $document->id);
    })
      ->where('status', 'active')
      ->whereNotNull('dyn_asiento_number')
      ->value('dyn_asiento_number');

    if (!$asientoNumber) {
      throw new Exception("No se encontró el número de asiento para el documento {$document->id}.");
    }

    // Sincronizar detalle
    $resource = new TraverseAccountingEntryDetailResource($document, $asientoNumber, $this->isReversal);
    $details = $resource->toArray(request());

    $detailLog->markAsInProgress();

    foreach ($details as $detail) {
      $syncService->sync('accounting_entry_detail', $detail, 'create');
    }

    $detailLog->updateProcesoEstado(0);

    Log::info('Detalle de asiento contable sincronizado', [
      'electronic_document_id' => $document->id,
      'asiento_number' => $asientoNumber,
      'lines' => count($details),
    ]);
  }

  /**
   * Handle a job failure.
   */
  public function failed(Throwable $exception): void
  {
    Log::error('VerifyAndMigrateTraverseJob failed definitivamente', [
      'electronic_document_id' => $this->electronicDocumentId,
      'is_reversal' => $this->isReversal,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString(),
    ]);

    // Marcar documento como failed
    $document = ElectronicDocument::find($this->electronicDocumentId);
    if ($document) {
      $document->update(['traverse_migration_status' => 'failed']);
    }

    // Marcar logs como failed
    VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $this->electronicDocumentId)
      ->whereIn('step', $this->isReversal
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
        ]
      )
      ->update([
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
        'error_message' => $exception->getMessage(),
      ]);
  }
}