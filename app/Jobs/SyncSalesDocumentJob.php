<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\SalesDocumentDynamicsResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\gestionsistema\Company;
use App\Services\Dynamics\SalesDynamicsBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncSalesDocumentJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 2; // Reducido de 3 → 2 para evitar crecimiento exponencial de jobs
  public int $timeout = 300;
  public int $backoff = 120; // Aumentado a 120 segundos para dar más tiempo entre reintentos

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $electronicDocumentId
  )
  {
    $this->onQueue('electronic_documents');
  }

  /**
   * Execute the job.
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    try {
      $this->processElectronicDocument($this->electronicDocumentId, $syncService);
    } catch (\Exception $e) {
      Log::error('Error en SyncSalesDocumentJob', [
        'electronic_document_id' => $this->electronicDocumentId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      throw $e;
    }
  }

  /**
   * Procesa un documento electrónico específico
   */
  protected function processElectronicDocument(int $electronicDocumentId, DatabaseSyncService $syncService): void
  {
    $document = ElectronicDocument::with([
      'client',
      'items',
      'currency',
      'creator',
      'vehicleMovement.vehicle',
      'purchaseRequestQuote.accessories.approvedAccessory',
    ])->find($electronicDocumentId);

    if (!$document) {
      Log::error('Documento electrónico no encontrado', ['id' => $electronicDocumentId]);
      return;
    }

    // Validar que el documento esté en estado válido para sincronización
    if ($document->anulado) {
      Log::warning('Documento electrónico anulado, no se sincronizará', ['id' => $electronicDocumentId]);
      return;
    }

    $document->markAsInProgress();

    // 1. Sincronizar cliente (si no existe en Dynamics)
    $this->syncClient($document, $syncService);

    // 2. Sincronizar detalle de venta (incluyendo anticipos con valores negativos)
    $this->syncSalesDocumentDetail($document, $syncService);

    // 3. Sincronizar documento de venta (cabecera)
    $this->syncSalesDocument($document, $syncService);

    // 4. Verificar si algún paso falló y actualizar el estado del documento
    $this->checkAndUpdateCompletionStatus($document);
  }

  protected function checkAndUpdateCompletionStatus(ElectronicDocument $document): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)->get();

    if ($logs->isEmpty()) {
      return;
    }

    $hasFailed = $logs->contains(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED);

    if ($hasFailed) {
      $document->markAsFailed();
    }
  }

  /**
   * Sincroniza el cliente en Dynamics si no existe
   */
  protected function syncClient(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_CLIENT,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_CLIENT],
      $document->cliente_numero_de_documento
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Verificar si el cliente ya existe en la BD intermedia
      $existingClient = DB::connection('dbtp')
        ->table('neInTbCliente')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('Cliente', $document->cliente_numero_de_documento)
        ->first();

      if ($existingClient) {
        $log->updateProcesoEstado(
          $existingClient->ProcesoEstado ?? 0,
          $existingClient->ProcesoError ?? null
        );
        return;
      }

      // Si tiene relación con BusinessPartner, sincronizar
      if ($document->client) {
        $log->markAsInProgress();

        $clientData = [
          'id' => $document->client->id,
          'num_doc' => $document->client->num_doc,
          'full_name' => $document->client->full_name,
          'document_type_id' => $document->client->document_type_id,
          'tax_class_type_id' => $document->client->tax_class_type_id,
          'type_person_id' => $document->client->type_person_id,
          'paternal_surname' => $document->client->paternal_surname,
          'maternal_surname' => $document->client->maternal_surname,
          'first_name' => $document->client->first_name,
          'middle_name' => $document->client->middle_name,
        ];

        $syncService->sync('business_partners', $clientData);
        $log->updateProcesoEstado(0);

      } else {
        // Cliente sin relación BusinessPartner, marcar como completado
        $log->updateProcesoEstado(1);
      }
    } catch (\Exception $e) {
      $log->markAsFailed($e->getMessage());
      Log::error('Error al sincronizar cliente', [
        'document_id' => $document->id,
        'client_id' => $document->cliente_numero_de_documento,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Sincroniza la cabecera del documento de venta
   */
  protected function syncSalesDocument(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    $documentoId = $document->full_number;

    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT],
      $documentoId
    );

    $detailLog = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_DETAIL],
      $documentoId
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED && $document->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar que el cliente esté procesado primero
    $clientLog = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_SALES_CLIENT)
      ->first();

    if (!$clientLog || $clientLog->proceso_estado !== 1) {
      Log::info('Esperando que el cliente sea procesado antes de sincronizar documento', [
        'document_id' => $document->id,
        'client_log_status' => $clientLog?->proceso_estado
      ]);
      return;
    }

    // Verificar en la BD intermedia si ya existe
    $existingDocument = DB::connection('dbtp')
      ->table('neInTbVenta')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('DocumentoId', $documentoId)
      ->first();

    if (!$existingDocument) {
      // No existe, intentar sincronizar
      try {
        $log->markAsInProgress();

        if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
          // Transformar documento usando el Resource
          $resource = new SalesDocumentDynamicsResource($document);
          $data = $resource->toArray(request());
          $syncService->sync('sales_document', $data);
          $log->updateProcesoEstado(0);
        }
      } catch (\Exception $e) {
        $log->markAsFailed($e->getMessage());
        Log::error('Error al sincronizar documento de venta', [
          'document_id' => $document->id,
          'error' => $e->getMessage(),
        ]);
        throw $e;
      }
    } else if ($detailLog) {
      // Existe, actualizar el estado del log
      $log->updateProcesoEstado(
        $existingDocument->ProcesoEstado ?? 0,
        $existingDocument->ProcesoError ?? null
      );

      if ($existingDocument->ProcesoEstado == 1 && $detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        $log->markAsCompletedElectronicDocument();
      }
    }
  }

  /**
   * Sincroniza el detalle del documento de venta
   */
  protected function syncSalesDocumentDetail(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    $documentoId = $document->full_number;

    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_DETAIL],
      $documentoId
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar en la BD intermedia si ya existe el detalle
    $existingDetail = DB::connection('dbtp')
      ->table('neInTbVentaDt')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('DocumentoId', $documentoId)
      ->first();

    if (!$existingDetail) {
      // No existe, intentar sincronizar
      try {
        $log->markAsInProgress();

        $this->syncDocumentItems($document, $syncService);

        $log->updateProcesoEstado(0);
      } catch (\Exception $e) {
        $log->markAsFailed($e->getMessage());
        Log::error('Error al sincronizar detalle de venta', [
          'document_id' => $document->id,
          'error' => $e->getMessage(),
        ]);
        throw $e;
      }
    } else {
      // Existe, actualizar el estado del log
      $log->markAsCompleted(1);
    }
  }

  /**
   * Sincroniza los items del documento electrónico a Dynamics
   * Maneja dos tipos de consolidación:
   * - simple: usa los items directamente de ap_billing_electronic_document_items
   * - massive: obtiene los items desde las órdenes de trabajo vinculadas a través de notas internas
   */
  private function syncDocumentItems(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    // Determinar el tipo de consolidación del documento
    $consolidationType = $document->consolidation_type;

    // Para tipo 'massive', procesar items desde las notas internas y sus órdenes de trabajo
    if ($consolidationType === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $this->syncDocumentItemsForMassiveConsolidation($document, $syncService);
      return;
    }

    // Para tipo 'simple' o sin consolidación, usar la lógica estándar
    $this->syncDocumentItemsForSimpleConsolidation($document, $syncService);
  }

  /**
   * Sincroniza items para consolidación simple (lógica original)
   * Usa los items directamente desde ap_billing_electronic_document_items
   */
  private function syncDocumentItemsForSimpleConsolidation(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    // Construir los items usando el builder estándar
    $builder = new SalesDynamicsBuilder();

    // Sincronizar cada item construido
    foreach ($builder->buildItems($document) as $item) {
      $data = is_array($item) ? $item : $item->toArray(request());
      $syncService->sync('sales_document_detail', $data);
    }
  }

  /**
   * Sincroniza items para consolidación masiva
   * Obtiene los items desde las órdenes de trabajo asociadas a través de notas internas
   * Procesa cada OT individualmente y construye los items correspondientes
   */
  private function syncDocumentItemsForMassiveConsolidation(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    // Obtener las notas internas asociadas al documento electrónico con sus relaciones necesarias
    // Incluye: orden de trabajo, repuestos con producto y unidad de medida, y mano de obra
    $internalNotes = $document->internalNotes()->with([
      'workOrder.parts.product.unitMeasurement',
      'workOrder.labours'
    ])->get();

    // Validar que existan notas internas
    if ($internalNotes->isEmpty()) {
      Log::warning('No se encontraron notas internas para consolidación masiva', [
        'document_id' => $document->id,
        'consolidation_type' => $document->consolidation_type
      ]);
      return;
    }

    // Número de línea inicial para los items del documento
    $lineNumber = 1;

    // Procesar cada nota interna y su orden de trabajo asociada
    foreach ($internalNotes as $internalNote) {
      // Obtener la orden de trabajo relacionada con la nota interna
      $workOrder = $internalNote->workOrder;

      // Validar que la nota interna tenga una orden de trabajo asociada
      if (!$workOrder) {
        Log::warning('Nota interna sin orden de trabajo asociada', [
          'internal_note_id' => $internalNote->id,
          'document_id' => $document->id
        ]);
        continue;
      }

      // Procesar los items de la orden de trabajo actual
      $lineNumber = $this->processWorkOrderItems($workOrder, $document, $syncService, $lineNumber);
    }
  }

  /**
   * Procesa los items (parts y labours) de una orden de trabajo
   * Construye los items en formato Dynamics y los sincroniza
   *
   * @param ApWorkOrder $workOrder Orden de trabajo a procesar
   * @param ElectronicDocument $document Documento electrónico padre
   * @param DatabaseSyncService $syncService Servicio de sincronización
   * @param int $lineNumber Número de línea inicial para los items
   * @return int Siguiente número de línea disponible después de procesar todos los items
   */
  private function processWorkOrderItems(
    ApWorkOrder $workOrder,
    ElectronicDocument $document,
    DatabaseSyncService $syncService,
    int $lineNumber
  ): int {
    // Obtener el código de cuenta contable para mano de obra (labour)
    $labourCode = ApAccountingAccountPlan::find(ApAccountingAccountPlan::LABOUR_ACCOUNT_ID)?->code_dynamics ?? 'V0000011';

    // Obtener el código de cuenta contable para materiales
    $materialsCode = ApAccountingAccountPlan::find(ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID)?->code_dynamics ?? 'V0000012';

    // Calcular el divisor de IGV (por ejemplo, 1.18 para 18% de IGV)
    $igvDivisor = 1 + ($document->porcentaje_de_igv / 100);

    // Obtener el almacén del documento
    $warehouse = $document->warehouse();

    // ============================================================
    // PROCESAR PARTS (REPUESTOS/PRODUCTOS) DE LA ORDEN DE TRABAJO
    // ============================================================

    foreach ($workOrder->parts as $part) {
      // Validar que el part tenga un producto asociado
      if (!$part->product) {
        Log::warning('Part sin producto asociado', [
          'part_id' => $part->id,
          'work_order_id' => $workOrder->id,
          'document_id' => $document->id
        ]);
        continue;
      }

      // Validar que el producto tenga código de Dynamics
      if (!$part->product->dyn_code) {
        Log::warning('Producto sin código de Dynamics', [
          'product_id' => $part->product->id,
          'part_id' => $part->id,
          'work_order_id' => $workOrder->id,
          'document_id' => $document->id
        ]);
        continue;
      }

      // Calcular precio unitario sin IGV
      $unitPriceWithTax = (float) $part->unit_price;
      $unitPricePreTax = round($unitPriceWithTax / $igvDivisor, 2);

      // Calcular cantidad (usar quantity_used si está disponible, sino assigned_quantity)
      $quantity = (float) ($part->quantity_used ?? $part->assigned_quantity ?? 0);

      // Validar que haya cantidad
      if ($quantity <= 0) {
        Log::warning('Part sin cantidad válida', [
          'part_id' => $part->id,
          'work_order_id' => $workOrder->id,
          'document_id' => $document->id
        ]);
        continue;
      }

      // Calcular precio total sin IGV
      $totalPricePreTax = round($quantity * $unitPricePreTax, 2);

      // Obtener descripción del producto
      $description = Str::upper($part->product->description ?? $part->product->name ?? 'PRODUCTO');

      // Obtener unidad de medida del producto - Si el producto tiene unidad de medida, usarla; sino usar 'UND'
      $unitMeasurementCode = $part->product->unitMeasurement->dyn_code ?? 'UND';

      // Construir el item en formato Dynamics
      $itemData = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'DocumentoId' => $document->full_number,
        'Linea' => $lineNumber,
        'ArticuloId' => $part->product->dyn_code,
        'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
        'ArticuloDescripcionLarga' => $description,
        'SitioId' => $warehouse,
        'UnidadMedidaId' => $unitMeasurementCode,
        'Cantidad' => $quantity,
        'PrecioUnitario' => $unitPricePreTax,
        'DescuentoUnitario' => 0,
        'PrecioTotal' => $totalPricePreTax,
      ];

      // Sincronizar el item de part
      $syncService->sync('sales_document_detail', $itemData);

      // Incrementar número de línea para el siguiente item
      $lineNumber++;
    }

    // ============================================================
    // PROCESAR LABOURS (MANO DE OBRA) DE LA ORDEN DE TRABAJO
    // ============================================================

    foreach ($workOrder->labours as $labour) {
      // Calcular precio unitario sin IGV
      $unitPriceWithTax = (float) $labour->hourly_rate;
      $unitPricePreTax = round($unitPriceWithTax / $igvDivisor, 2);

      // Calcular tiempo en horas decimales
      $timeSpentHours = $labour->time_spent_decimal ?? 0;

      // Validar que haya tiempo registrado
      if ($timeSpentHours <= 0) {
        Log::warning('Labour sin tiempo registrado', [
          'labour_id' => $labour->id,
          'work_order_id' => $workOrder->id,
          'document_id' => $document->id
        ]);
        continue;
      }

      // Calcular precio total sin IGV
      $totalPricePreTax = round($timeSpentHours * $unitPricePreTax, 2);

      // Obtener descripción del labour
      $description = Str::upper($labour->description ?? 'MANO DE OBRA');

      // Determinar el código de artículo según el tipo de labour
      $descripcionNormalizada = trim(strtolower($labour->description ?? ''));
      $articuloId = ($descripcionNormalizada === 'materiales') ? $materialsCode : $labourCode;

      // Construir el item en formato Dynamics
      $itemData = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'DocumentoId' => $document->full_number,
        'Linea' => $lineNumber,
        'ArticuloId' => $articuloId,
        'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
        'ArticuloDescripcionLarga' => $description,
        'SitioId' => $warehouse,
        'UnidadMedidaId' => 'UND',
        'Cantidad' => $timeSpentHours,
        'PrecioUnitario' => $unitPricePreTax,
        'DescuentoUnitario' => 0,
        'PrecioTotal' => $totalPricePreTax,
      ];

      // Sincronizar el item de labour
      $syncService->sync('sales_document_detail', $itemData);

      // Incrementar número de línea para el siguiente item
      $lineNumber++;
    }

    // Retornar el siguiente número de línea disponible
    return $lineNumber;
  }

  /**
   * Obtiene o crea un log de migración
   */
  protected function getOrCreateLog(
    int     $electronicDocumentId,
    string  $step,
    string  $tableName,
    string  $externalId,
    ?int    $vehicleId = null,
    ?string $uniqueKey = null
  ): VehiclePurchaseOrderMigrationLog
  {
    $query = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $electronicDocumentId)
      ->where('step', $step);

    // Si hay una clave única adicional (como código de artículo), buscar por ella
    if ($uniqueKey) {
      $query->where('external_id', $uniqueKey);
    }

    $log = $query->first();

    if (!$log) {
      $log = VehiclePurchaseOrderMigrationLog::create([
        'electronic_document_id' => $electronicDocumentId,
        'ap_vehicles_id' => $vehicleId,
        'step' => $step,
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'table_name' => $tableName,
        'external_id' => $externalId,
        'proceso_estado' => 0,
        'attempts' => 0,
      ]);
    }

    return $log;
  }

  /**
   * Maneja el fallo del job
   */
  public function failed(\Throwable $exception): void
  {
    Log::error('SyncSalesDocumentJob failed', [
      'electronic_document_id' => $this->electronicDocumentId,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString(),
    ]);
  }
}
