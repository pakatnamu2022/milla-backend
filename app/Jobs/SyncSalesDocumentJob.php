<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\SalesDocumentDetailDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentSerialDynamicsResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function json_encode;

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

        $this->syncDocumentItems($document, $syncService, $documentoId);

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
   * Envía los ítems del documento y, si corresponde, las líneas adicionales de accesorios de posventa.
   * Los anticipos se envían sin modificaciones; los documentos de venta final con accesorios de posventa
   * reemplazan el precio del ítem del vehículo por el precio base de la cotización y agregan una línea
   * por cada accesorio de posventa.
   */
  private function syncDocumentItems(ElectronicDocument $document, DatabaseSyncService $syncService, string $documentoId): void
  {
    $postSaleAccessories = !$document->is_advance_payment
      ? $this->getPostSaleAccessories($document)
      : collect();

    $igvDivisor = $this->getIgvDivisor($document);
    $nextLine = $document->items->max('line_number') + 1;

    // Enviar los ítems propios del documento electrónico
    foreach ($document->items as $item) {
      $overridePrice = $this->resolveVehicleBasePrice($item, $postSaleAccessories, $document, $igvDivisor);

      $resource = new SalesDocumentDetailDynamicsResource($item, $document, $overridePrice);
      $syncService->sync('sales_document_detail', $resource->toArray(request()));
    }

    // Enviar una línea por cada accesorio de posventa (solo documentos no anticipo)
    foreach ($postSaleAccessories as $accessory) {
      $data = $this->buildAccessoryLine($accessory, $document, $documentoId, $nextLine++, $igvDivisor);
      $syncService->sync('sales_document_detail', $data);
    }
  }

  /**
   * Devuelve el divisor IGV a partir del porcentaje configurado en el documento (ej. 1.18 para 18%).
   */
  private function getIgvDivisor(ElectronicDocument $document): float
  {
    return 1 + ($document->porcentaje_de_igv / 100);
  }

  /**
   * Si el documento tiene accesorios de posventa y el ítem no es una regularización de anticipo,
   * devuelve el precio base del vehículo sin IGV (tomado de la cotización) para usarlo como override
   * en el resource. Si no aplica, devuelve null y el resource usa su propio valor_unitario.
   */
  private function resolveVehicleBasePrice(
    $item,
    Collection $postSaleAccessories,
    ElectronicDocument $document,
    float $igvDivisor
  ): ?float
  {
    if ($postSaleAccessories->isEmpty() || $item->anticipo_regularizacion) {
      return null;
    }

    $totalAccessoriesWithIgv = $postSaleAccessories->sum(
      fn($a) => $this->accessoryUnitGrossInDocCurrency($a, $document) * $a->quantity
    );

    return round(
      ((float)$document->purchaseRequestQuote->base_selling_price - $totalAccessoriesWithIgv) / $igvDivisor,
      2
    );
  }

  /**
   * Devuelve el precio unitario bruto (con IGV) del accesorio convertido a la moneda del documento.
   * Si el accesorio está en PEN y el documento en USD, divide por tipo_de_cambio.
   */
  private function accessoryUnitGrossInDocCurrency($accessory, ElectronicDocument $document): float
  {
    $gross = (float)($accessory->price + $accessory->additional_price);

    if (
      $accessory->type_currency_id === TypeCurrency::PEN_ID &&
      $document->currency->iso_code === TypeCurrency::USD
    ) {
      return $gross / (float)$document->tipo_de_cambio;
    }

    if (
      $accessory->type_currency_id === TypeCurrency::USD_ID &&
      $document->currency->iso_code === TypeCurrency::PEN
    ) {
      return $gross * (float)$document->tipo_de_cambio;
    }

    return $gross;
  }

  /**
   * Construye el array de datos para una línea de accesorio de posventa en Dynamics.
   * El precio se convierte a valor sin IGV dividiendo por el igvDivisor.
   */
  private function buildAccessoryLine(
    $accessory,
    ElectronicDocument $document,
    string $documentoId,
    int $linea,
    float $igvDivisor
  ): array
  {
    $unitPricePreTax = round($this->accessoryUnitGrossInDocCurrency($accessory, $document) / $igvDivisor, 2);
    $description = Str::upper($accessory->approvedAccessory->description);

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'DocumentoId' => $documentoId,
      'Linea' => $linea,
      'ArticuloId' => $accessory->approvedAccessory->code_dynamics ?? throw new Exception("El accesorio '{$accessory->approvedAccessory->code}' no tiene código Dynamics (code_dynamics) definido."),
      'ArticuloDescripcionCorta' => Str::upper(Str::limit($description, 60, '')),
      'ArticuloDescripcionLarga' => $description,
      'SitioId' => $document->warehouse() ?? throw new Exception('El documento no tiene almacén asociado.'),
      'UnidadMedidaId' => 'UND',
      'Cantidad' => $accessory->quantity,
      'PrecioUnitario' => $unitPricePreTax,
      'DescuentoUnitario' => 0,
      'PrecioTotal' => round($accessory->quantity * $unitPricePreTax, 2),
    ];
  }

  /**
   * Retorna los accesorios de posventa (ACCESORIO_ADICIONAL con type_operation_id = TIPO_OPERACION_POSTVENTA)
   * asociados a la PurchaseRequestQuote del documento.
   */
  private function getPostSaleAccessories(ElectronicDocument $document): Collection
  {
    return $document->purchaseRequestQuote?->accessories
      ->filter(fn($a) =>
        $a->type === 'ACCESORIO_ADICIONAL' &&
        $a->approvedAccessory?->type_operation_id === ApMasters::TIPO_OPERACION_POSTVENTA
      ) ?? collect();
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
