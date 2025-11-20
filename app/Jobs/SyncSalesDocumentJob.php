<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\SalesDocumentDetailDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentSerialDynamicsResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function json_encode;

class SyncSalesDocumentJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;
  public int $backoff = 60;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $electronicDocumentId
  )
  {
    $this->onQueue('sync');
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

    // 1. Sincronizar cliente (si no existe en Dynamics)
    $this->syncClient($document, $syncService);

    // 2. Sincronizar artículos de los items
//    $this->syncArticles($document, $syncService);

    // 3. Sincronizar documento de venta (cabecera)
    $this->syncSalesDocument($document, $syncService);

    // 4. Sincronizar detalle de venta (incluyendo anticipos con valores negativos)
    $this->syncSalesDocumentDetail($document, $syncService);

    // 5. Sincronizar series (VIN) si existen
//    $this->syncSalesDocumentSerial($document, $syncService);
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
   * Sincroniza los artículos de los items del documento
   */
  protected function syncArticles(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    foreach ($document->items as $item) {
      $codigo = $item->accountPlan->code_dynamics;

      $log = $this->getOrCreateLog(
        $document->id,
        VehiclePurchaseOrderMigrationLog::STEP_SALES_ARTICLE,
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_ARTICLE],
        $codigo,
        null,
        $codigo
      );

      if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      try {
        // Verificar si el artículo ya existe en la BD intermedia
        $existingArticle = DB::connection('dbtp')
          ->table('neInTbArticulo')
          ->where('EmpresaId', Company::AP_DYNAMICS)
          ->where('Articulo', $codigo)
          ->first();

        if ($existingArticle) {
          $log->updateProcesoEstado(
            $existingArticle->ProcesoEstado ?? 0,
            $existingArticle->ProcesoError ?? null
          );
          continue;
        } else {
          $log->markAsFailed('Artículo no encontrado en Dynamics: ' . $codigo);
        }
      } catch (\Exception $e) {
        $log->markAsFailed($e->getMessage());
        Log::error('Error al verificar artículo', [
          'document_id' => $document->id,
          'article_code' => $codigo,
          'error' => $e->getMessage(),
        ]);
      }
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

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
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

        // Transformar documento usando el Resource
        $resource = new SalesDocumentDynamicsResource($document);
        $data = $resource->toArray(request());

        $syncService->sync('sales_document', $data);
        $log->updateProcesoEstado(0);

      } catch (\Exception $e) {
        $log->markAsFailed($e->getMessage());
        Log::error('Error al sincronizar documento de venta', [
          'document_id' => $document->id,
          'error' => $e->getMessage(),
        ]);
        throw $e;
      }
    } else {
      // Existe, actualizar el estado del log
      $log->updateProcesoEstado(
        $existingDocument->ProcesoEstado ?? 0,
        $existingDocument->ProcesoError ?? null
      );
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

        foreach ($document->items as $item) {
          // Transformar item usando el Resource
          $resource = new SalesDocumentDetailDynamicsResource($item, $document);
          $data = $resource->toArray(request());

          $syncService->sync('sales_document_detail', $data);
        }

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
   * Sincroniza las series (VIN) del documento de venta
   */
  protected function syncSalesDocumentSerial(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    // Solo sincronizar series si el documento está relacionado con vehículos
    if (!$document->vehicleMovement?->vehicle) {
      return;
    }

    $documentoId = $document->full_number;
    $vehicleId = $document->vehicleMovement->vehicle->id ?? null;

    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_SERIAL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_SERIAL],
      $documentoId,
      $vehicleId
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar que el detalle del documento esté procesado primero
    $detailLog = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_DETAIL)
      ->first();

    if (!$detailLog || $detailLog->proceso_estado !== 1) {
      Log::info('Esperando que el detalle sea procesado antes de sincronizar series', [
        'document_id' => $document->id,
        'detail_log_status' => $detailLog?->proceso_estado
      ]);
      return;
    }

    // Verificar en la BD intermedia si ya existe la serie
    $existingSerial = DB::connection('dbtp')
      ->table('neInTbVentaDtS')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('DocumentoId', $documentoId)
      ->first();

    if (!$existingSerial) {
      // No existe, intentar sincronizar
      try {
        $log->markAsInProgress();

        $vehicle = $document->vehicleMovement->vehicle;

        foreach ($document->items as $index => $item) {
          // Crear resource para la serie (VIN)
          $resource = new SalesDocumentSerialDynamicsResource($document);
          $data = $resource->toArray(request());

          $syncService->sync('sales_document_serial', $data);
        }

        $log->updateProcesoEstado(0);
      } catch (\Exception $e) {
        $log->markAsFailed($e->getMessage());
        Log::error('Error al sincronizar serie de venta', [
          'document_id' => $document->id,
          'error' => $e->getMessage(),
        ]);
        throw $e;
      }
    } else {
      // Existe, actualizar el estado del log
      $log->updateProcesoEstado(
        $existingSerial->ProcesoEstado ?? 0,
        $existingSerial->ProcesoError ?? null
      );
    }
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
