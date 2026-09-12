<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\AccountingEntryHeaderDynamicsResource;
use App\Http\Services\ap\facturacion\AccountingEntryService;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reversa en Dynamics el asiento contable (AccountEntry) que SyncAccountingEntryJob
 * generó cuando se contabilizó la entrega de una guía de VENTA.
 *
 * Se dispara desde ShippingGuidesService::cancel(): al anularse la guía CON reversión,
 * si la guía ya tenía un asiento original enviado a la intermedia (log
 * STEP_ACCOUNTING_ENTRY_HEADER), este job genera un nuevo asiento con las mismas
 * líneas pero con Débito/Crédito invertidos, y lo envía a
 * neInTbIntegracionAsientoCab/Det bajo una Referencia distinta (sufijo "-REV").
 *
 * Si la guía nunca llegó a contabilizarse (no existe el log original), el job
 * no hace nada: no hay nada que reversar.
 */
class ReverseAccountingEntryJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 180;
  public int $backoff = 60;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $shippingGuideId
  )
  {
    $this->onQueue('electronic_documents');
  }

  /**
   * Execute the job.
   */
  public function handle(
    DatabaseSyncService    $syncService,
    AccountingEntryService $accountingService
  ): void
  {
    try {
      Log::info('Iniciando reversión de asiento contable', [
        'shipping_guide_id' => $this->shippingGuideId
      ]);

      $shippingGuide = ShippingGuides::with([
        'vehicleMovement.vehicle.model.classArticle',
        'vehicleMovement',
      ])->find($this->shippingGuideId);

      if (!$shippingGuide) {
        Log::warning('ShippingGuide no encontrada para reversión de asiento', [
          'shipping_guide_id' => $this->shippingGuideId
        ]);
        return;
      }

      // 1. Solo hay algo que reversar si el asiento original fue enviado a la intermedia
      $originalHeaderLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER)
        ->first();

      if (!$originalHeaderLog) {
        Log::info('La guía no tiene asiento contable original enviado; no se requiere reversión', [
          'shipping_guide_id' => $shippingGuide->id,
        ]);
        return;
      }

      // 2. Idempotencia: si ya se generó (o está en curso) la reversión, no repetir
      $existingReversalLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER_REVERSAL)
        ->first();

      if ($existingReversalLog) {
        Log::info('La reversión del asiento ya fue generada previamente para esta guía', [
          'shipping_guide_id' => $shippingGuide->id,
          'status' => $existingReversalLog->status,
        ]);
        return;
      }

      // 3. Ubicar la factura sobre la que se generó el asiento original, usando la
      //    misma Referencia que quedó registrada en el log del asiento forward.
      $vin = $shippingGuide->vehicleMovement->vehicle->vin;

      $candidateDocuments = ElectronicDocument::with([
        'items',
        'creator.person',
        'currency',
        'seriesModel.sede',
        'vehicleMovement.vehicle.model.classArticle',
        'vehicle',
      ])
        ->where('is_advance_payment', 0)
        ->where('aceptada_por_sunat', true)
        ->whereIn('sunat_concept_document_type_id', [
          ElectronicDocument::TYPE_FACTURA,
          ElectronicDocument::TYPE_BOLETA,
        ])
        ->whereHas('vehicle', function ($query) use ($vin) {
          $query->where('vin', $vin);
        })
        ->get();

      $electronicDocument = $candidateDocuments->first(
        fn($inv) => AccountingEntryHeaderDynamicsResource::buildReferencia($inv->full_number, $vin) === $originalHeaderLog->external_id
      );

      if (!$electronicDocument) {
        Log::error('No se pudo ubicar la factura del asiento original para generar la reversión', [
          'shipping_guide_id' => $shippingGuide->id,
          'referencia_original' => $originalHeaderLog->external_id,
        ]);
        return;
      }

      // 4. Construir Referencia de reversión (distinta a la original, respetando 30 chars)
      $reversalReferencia = $this->buildReversalReferencia($originalHeaderLog->external_id);

      // 5. Crear logs de reversión
      $headerLog = $this->getOrCreateLog(
        $shippingGuide->id,
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER_REVERSAL,
        'neInTbIntegracionAsientoCab',
        $reversalReferencia
      );

      $detailLog = $this->getOrCreateLog(
        $shippingGuide->id,
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL_REVERSAL,
        'neInTbIntegracionAsientoDet',
        $reversalReferencia
      );

      // 6. Guardia anti-duplicado en la intermedia
      $alreadyExists = DB::connection('dbtp')
        ->table('neInTbIntegracionAsientoCab')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('Referencia', $reversalReferencia)
        ->exists();

      if ($alreadyExists) {
        Log::info('ReverseAccountingEntryJob: registro ya existe en intermedia, se omite re-inserción', [
          'shipping_guide_id' => $shippingGuide->id,
          'referencia' => $reversalReferencia,
        ]);
        $headerLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED]);
        $detailLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED]);
        return;
      }

      // 7. Generar número de asiento e insertar header bajo lock distribuido
      $asientoNumber = null;
      $lock = Cache::lock('sync_accounting_entry_asiento', 30);
      try {
        $lock->block(20);

        $asientoNumber = $accountingService->getNextAsientoNumber();

        Log::info('Número de asiento de reversión generado', [
          'shipping_guide_id' => $shippingGuide->id,
          'asiento_number' => $asientoNumber
        ]);

        $headerLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);

        $fecha = $shippingGuide->cancelled_at ?? now();
        $headerResource = new AccountingEntryHeaderDynamicsResource($electronicDocument, $fecha, $asientoNumber);
        $headerData = $headerResource->toArray(request());
        // La Referencia del recurso corresponde al asiento original: se sobreescribe con la de reversión.
        $headerData['Referencia'] = $reversalReferencia;

        $syncService->sync('accounting_entry_header', $headerData, 'create');
        $headerLog->update(['proceso_estado' => 0]);
      } finally {
        $lock->forceRelease();
      }

      Log::info('Cabecera de reversión de asiento sincronizada', [
        'shipping_guide_id' => $shippingGuide->id,
        'asiento_number' => $asientoNumber
      ]);

      // 8. Generar líneas del asiento original e invertir Débito/Crédito
      $detailLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);

      $forwardLines = $accountingService->generateAccountingLines($electronicDocument, $asientoNumber);

      $reversedLines = array_map(function (array $line) {
        return array_merge($line, [
          'Debito' => $line['Credito'],
          'Credito' => $line['Debito'],
          // Descripcion es varchar(30) NOT NULL en neInTbIntegracionAsientoDet: prefijo
          // corto ("REV: ") en vez de "REVERSION: " porque varias descripciones originales
          // (p.ej. "Reversion precio unitario", 25 chars) ya rozan el límite y con el
          // prefijo largo lo superaban, truncado defensivo por si acaso.
          'Descripcion' => substr('REV: ' . $line['Descripcion'], 0, 30),
        ]);
      }, $forwardLines);

      // El balance se mantiene por construcción (mismo monto, lados invertidos); se valida igual.
      $accountingService->validateBalance($reversedLines);

      foreach ($reversedLines as $line) {
        $syncService->sync('accounting_entry_detail', $line, 'create');
      }

      $detailLog->update(['proceso_estado' => 0]);

      Log::info('Reversión de asiento contable sincronizada exitosamente', [
        'shipping_guide_id' => $shippingGuide->id,
        'asiento_number' => $asientoNumber,
        'total_lines' => count($reversedLines),
        'electronic_document' => $electronicDocument->full_number,
        'referencia_original' => $originalHeaderLog->external_id,
        'referencia_reversion' => $reversalReferencia,
      ]);

      $headerLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED]);
      $detailLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED]);

    } catch (Exception $e) {
      Log::error('Error en ReverseAccountingEntryJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      throw $e;
    }
  }

  /**
   * Construye la Referencia de reversión a partir de la Referencia original,
   * reservando espacio para el sufijo "-REV" dentro del límite de 30 chars de Dynamics.
   */
  protected function buildReversalReferencia(string $originalReferencia): string
  {
    $suffix = '-REV';
    $base = substr($originalReferencia, 0, 30 - strlen($suffix));
    return $base . $suffix;
  }

  /**
   * Obtiene o crea un log de migración
   */
  protected function getOrCreateLog(
    int    $shippingGuideId,
    string $step,
    string $tableName,
    string $externalId
  ): VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'shipping_guide_id' => $shippingGuideId,
        'step' => $step,
      ],
      [
        'table_name' => $tableName,
        'external_id' => $externalId,
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'attempts' => 0,
      ]
    );
  }

  /**
   * Handle a job failure.
   */
  public function failed(Throwable $exception): void
  {
    Log::error('ReverseAccountingEntryJob failed definitivamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString()
    ]);

    VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $this->shippingGuideId)
      ->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL_REVERSAL,
      ])
      ->update([
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
        'error_message' => $exception->getMessage(),
      ]);
  }
}
