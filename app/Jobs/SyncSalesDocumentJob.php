<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\SalesDocumentAdvanceDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentDetailDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentDynamicsResource;
use App\Http\Resources\Dynamics\SalesDocumentSerialDynamicsResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    $this->syncArticles($document, $syncService);

    // 3. Sincronizar documento de venta (cabecera)
    $this->syncSalesDocument($document, $syncService);

    // 4. Sincronizar detalle de venta
    $this->syncSalesDocumentDetail($document, $syncService);

    // 5. Sincronizar series (VIN) si existen
    $this->syncSalesDocumentSerial($document, $syncService);

    // 6. Sincronizar anticipos si existen
    $this->syncSalesDocumentAdvance($document, $syncService);
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
        $log->markAsCompleted($existingClient->ProcesoEstado ?? 1);
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

        $result = $syncService->sync('business_partners', $clientData, 'create');

        if ($result['success']) {
          $log->markAsCompleted(0);
        } else {
          $log->markAsFailed($result['message'] ?? 'Error al sincronizar cliente');
        }
      } else {
        // Cliente sin relación BusinessPartner, marcar como completado
        $log->markAsCompleted(1);
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
      $log = $this->getOrCreateLog(
        $document->id,
        VehiclePurchaseOrderMigrationLog::STEP_SALES_ARTICLE,
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_ARTICLE],
        $item->codigo,
        null,
        $item->codigo
      );

      if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      try {
        // Verificar si el artículo ya existe en la BD intermedia
        $existingArticle = DB::connection('dbtp')
          ->table('neInTbArticulo')
          ->where('EmpresaId', Company::AP_DYNAMICS)
          ->where('Articulo', $item->codigo)
          ->first();

        if ($existingArticle) {
          $log->markAsCompleted($existingArticle->ProcesoEstado ?? 1);
          continue;
        }

        // Si no existe, marcar como completado (asumiendo que ya existe en Dynamics)
        // TODO: Implementar sincronización de artículos si es necesario
        $log->markAsCompleted(1);
      } catch (\Exception $e) {
        $log->markAsFailed($e->getMessage());
        Log::error('Error al verificar artículo', [
          'document_id' => $document->id,
          'article_code' => $item->codigo,
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
    // Determinar el TipoId basado en el tipo de documento
    $tipoId = match ($document->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA => '01',
      ElectronicDocument::TYPE_BOLETA => '03',
      ElectronicDocument::TYPE_NOTA_CREDITO => '07',
      ElectronicDocument::TYPE_NOTA_DEBITO => '08',
      default => '01',
    };

    $documentoId = "{$tipoId}-{$document->serie}-{$document->numero}";

    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT],
      $documentoId
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $log->markAsInProgress();

      // Transformar documento usando el Resource
      $resource = new SalesDocumentDynamicsResource($document);
      $data = $resource->toArray(request());

      $result = $syncService->sync('sales_document', $data, 'create');

      if ($result['success']) {
        $log->markAsCompleted(0);
      } else {
        $log->markAsFailed($result['message'] ?? 'Error al sincronizar documento de venta');
      }
    } catch (\Exception $e) {
      $log->markAsFailed($e->getMessage());
      Log::error('Error al sincronizar documento de venta', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Sincroniza el detalle del documento de venta
   */
  protected function syncSalesDocumentDetail(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    $tipoId = match ($document->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA => '01',
      ElectronicDocument::TYPE_BOLETA => '03',
      ElectronicDocument::TYPE_NOTA_CREDITO => '07',
      ElectronicDocument::TYPE_NOTA_DEBITO => '08',
      default => '01',
    };

    $documentoId = "{$tipoId}-{$document->serie}-{$document->numero}";

    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_DETAIL],
      $documentoId
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $log->markAsInProgress();

      foreach ($document->items as $item) {
        // Transformar item usando el Resource
        $resource = new SalesDocumentDetailDynamicsResource($item, $document);
        $data = $resource->toArray(request());

        $result = $syncService->sync('sales_document_detail', $data, 'create');

        if (!$result['success']) {
          throw new \Exception($result['message'] ?? 'Error al sincronizar detalle de venta');
        }
      }

      $log->markAsCompleted(0);
    } catch (\Exception $e) {
      $log->markAsFailed($e->getMessage());
      Log::error('Error al sincronizar detalle de venta', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);
      throw $e;
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

    $tipoId = match ($document->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA => '01',
      ElectronicDocument::TYPE_BOLETA => '03',
      ElectronicDocument::TYPE_NOTA_CREDITO => '07',
      ElectronicDocument::TYPE_NOTA_DEBITO => '08',
      default => '01',
    };

    $documentoId = "{$tipoId}-{$document->serie}-{$document->numero}";

    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_SERIAL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_SERIAL],
      $documentoId
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $log->markAsInProgress();

      $vehicle = $document->vehicleMovement->vehicle;

      foreach ($document->items as $index => $item) {
        // Crear resource para la serie (VIN)
        $lineNumber = $item->line_number ?? ($index + 1);
        $resource = new SalesDocumentSerialDynamicsResource($document, $lineNumber, $vehicle->vin);
        $data = $resource->toArray(request());

        $result = $syncService->sync('sales_document_serial', $data, 'create');

        if (!$result['success']) {
          throw new \Exception($result['message'] ?? 'Error al sincronizar serie de venta');
        }
      }

      $log->markAsCompleted(0);
    } catch (\Exception $e) {
      $log->markAsFailed($e->getMessage());
      Log::error('Error al sincronizar serie de venta', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Sincroniza los anticipos aplicados al documento de venta
   */
  protected function syncSalesDocumentAdvance(ElectronicDocument $document, DatabaseSyncService $syncService): void
  {
    // Obtener items con anticipo regularizado
    $advanceItems = $document->items->filter(function ($item) {
      return $item->anticipo_regularizacion === true;
    });

    if ($advanceItems->isEmpty()) {
      return;
    }

    $tipoId = match ($document->sunat_concept_document_type_id) {
      ElectronicDocument::TYPE_FACTURA => '01',
      ElectronicDocument::TYPE_BOLETA => '03',
      ElectronicDocument::TYPE_NOTA_CREDITO => '07',
      ElectronicDocument::TYPE_NOTA_DEBITO => '08',
      default => '01',
    };

    $documentoId = "{$tipoId}-{$document->serie}-{$document->numero}";

    $log = $this->getOrCreateLog(
      $document->id,
      VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_ADVANCE,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALES_DOCUMENT_ADVANCE],
      $documentoId
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $log->markAsInProgress();

      foreach ($advanceItems as $item) {
        // Transformar anticipo usando el Resource
        $resource = new SalesDocumentAdvanceDynamicsResource($item, $document);
        $data = $resource->toArray(request());

        $result = $syncService->sync('sales_document_advance', $data, 'create');

        if (!$result['success']) {
          throw new \Exception($result['message'] ?? 'Error al sincronizar anticipo de venta');
        }
      }

      $log->markAsCompleted(0);
    } catch (\Exception $e) {
      $log->markAsFailed($e->getMessage());
      Log::error('Error al sincronizar anticipo de venta', [
        'document_id' => $document->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene o crea un log de migración
   */
  protected function getOrCreateLog(
    int $electronicDocumentId,
    string $step,
    string $tableName,
    string $externalId,
    ?int $vehicleId = null,
    ?string $uniqueKey = null
  ): VehiclePurchaseOrderMigrationLog {
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
}
