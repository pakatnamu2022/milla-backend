<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\TransferReceptionService;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\postventa\gestionProductos\TransferReception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * php artisan queue:work --tries=3
 */
class SyncShippingGuideDynamicsJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public ?int $shippingGuideId = null
  )
  {
    $this->onQueue('shipping_guide_sync');
  }

  /**
   * Execute the job.
   * Si se proporciona un ID, procesa solo esa guía
   * Si no, procesa todas las guías que no tienen dyn_series
   */
  public function handle(): void
  {
    try {
      if ($this->shippingGuideId) {
        $this->processShippingGuide($this->shippingGuideId);
      } else {
        $this->processAllShippingGuides();
      }
    } catch (\Exception $e) {
      Log::error('Error en SyncShippingGuideDynamicsJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Procesa todas las guías de remisión sin dyn_series
   */
  protected function processAllShippingGuides(): void
  {
    // Obtener guías que no tienen dyn_series sincronizado
    $shippingGuides = ShippingGuides::where('is_accounted', 0)
      ->whereNotNull('document_number')
      ->where('status', true)
      ->where('aceptada_por_sunat', true)
      ->where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->get();

    if ($shippingGuides->isEmpty()) {
      return;
    }

    foreach ($shippingGuides as $guide) {
      try {
        $this->processShippingGuide($guide->id);
      } catch (\Exception $e) {
        // Continuar con la siguiente guía
        Log::error('Error procesando guía en lote', [
          'shipping_guide_id' => $guide->id,
          'error' => $e->getMessage()
        ]);
        continue;
      }
    }
  }

  /**
   * Procesa una guía de remisión específica.
   * Separa el flujo según el tipo: comercial (venta) vs postventa (transferencia).
   */
  protected function processShippingGuide(int $shippingGuideId): void
  {
    $shippingGuide = ShippingGuides::find($shippingGuideId);

    if (!$shippingGuide || !$shippingGuide->document_number) {
      return;
    }

    try {
      // Guías comerciales de VENTA (dyn_series con prefijo CV-) → neIvConsultarAjustesInventario
      if (!empty($shippingGuide->dyn_series) && str_starts_with($shippingGuide->dyn_series, 'CV-')) {
        $this->processCommercialDeliveryGuide($shippingGuide);
        return;
      }

      // Guías de transferencia (POSTVENTA) → neIvConsultarTransferenciasInventario
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $transactionId = $shippingGuide->getDynamicsTransferTransactionId($isCancelled);
      $result = $this->consultStoredProcedure($transactionId);

      if (!$result) {
        Log::warning('No se encontró resultado en Dynamics para la guía', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId,
        ]);
        return;
      }

      $dynSeriesFromDynamics = isset($result->Documento) ? trim($result->Documento) : null;

      if (empty($dynSeriesFromDynamics)) {
        Log::warning('El resultado de Dynamics no contiene Serie', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId
        ]);
        return;
      }

      $isAccounted = ($result->Estado === 'CONTABILIZADO');

      $shippingGuide->update([
//        'dyn_series' => $dynSeriesFromDynamics,
        'is_accounted' => $isAccounted,
      ]);

      if (!$isAccounted) {
        Log::info('La transferencia aún no está contabilizada en Dynamics', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId
        ]);
        return;
      }

      $transferReception = TransferReception::where('shipping_guide_id', $shippingGuide->id)->first();

      if (!$transferReception) {
        Log::warning('No se encontró la recepción de transferencia asociada', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId
        ]);
        return;
      }

      $transferOutMovement = $transferReception->transferMovement;

      if (!$transferOutMovement) {
        Log::warning('No se encontró el movimiento de transferencia asociado', [
          'shipping_guide_id' => $shippingGuide->id,
          'transfer_reception_id' => $transferReception->id
        ]);
        return;
      }

      $transferReceptionService = new TransferReceptionService();
      $transferReceptionService->generateInventoryMovement($transferReception, $transferOutMovement);
    } catch (\Exception $e) {
      Log::error('Error procesando guía de remisión en Dynamics', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Procesa guías comerciales de VENTA usando neIvConsultarAjustesInventario.
   * Si el Numero del resultado coincide con el dyn_series → el movimiento fue hecho → entrega completa.
   */
  protected function processCommercialDeliveryGuide(ShippingGuides $shippingGuide): void
  {
    $results = $this->consultAjustesInventario($shippingGuide->dyn_series);

    $found = collect($results)->first(
      fn($row) => trim($row->Numero ?? '') === $shippingGuide->dyn_series
    );

    if (!$found) {
      return;
    }

    $shippingGuide->update(['is_accounted' => true]);

    ApVehicleDelivery::where('shipping_guide_id', $shippingGuide->id)
      ->update([
        'status_delivery' => 'completed',
        'real_delivery_date' => now(),
      ]);
  }

  /**
   * Consulta el Procedimiento Almacenado
   */
  protected function consultStoredProcedure(string $transactionId): ?object
  {
    try {
      // Ejecutar el PA: EXEC neIvConsultarTransferenciasInventario 'PTRA-00000157'
      $results = DB::connection('dbtest')
        ->select("EXEC neIvConsultarTransferenciasInventario '{$transactionId}'");

      // El PA debería retornar un resultado con el campo Serie (dyn_series)
      if (!empty($results) && isset($results[0])) {
        return $results[0];
      }

      return null;
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarTransferenciasInventario', [
        'transaction_id' => $transactionId,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Consulta neIvConsultarAjustesInventario para verificar si el movimiento comercial ya existe.
   * Retorna todos los resultados para que el caller filtre por Numero.
   */
  // TODO: Pasar $documentNumber como parámetro al SP cuando esté disponible en Dynamics
  protected function consultAjustesInventario(string $documentNumber): array
  {
    try {
      return DB::connection('dbtest')
        ->select("EXEC neIvConsultarAjustesInventario");
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarAjustesInventario', [
        'document_number' => $documentNumber,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('SyncShippingGuideDynamicsJob falló definitivamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString()
    ]);
  }
}
