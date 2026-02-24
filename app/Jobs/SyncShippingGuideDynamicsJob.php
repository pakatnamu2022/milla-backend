<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\TransferReceptionService;
use App\Models\ap\comercial\ShippingGuides;
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
    $shippingGuides = ShippingGuides::where(function ($query) {
      $query->whereNull('dyn_series')
        ->orWhere('dyn_series', '');
    })
      ->whereNotNull('document_number')
      ->where('status', true)
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
   * Procesa una guía de remisión específica
   * Similar a processPurchaseOrder en SyncInvoiceDynamicsJob
   */
  protected function processShippingGuide(int $shippingGuideId): void
  {
    $shippingGuide = ShippingGuides::find($shippingGuideId);

    if (!$shippingGuide) {
      return;
    }

    if (!$shippingGuide->document_number) {
      return;
    }

    // Consultar el PA para obtener los datos de la transferencia de inventario
    try {
      $result = $this->consultStoredProcedure($shippingGuide->document_number);

      if (!$result) {
        Log::warning('No se encontró resultado en Dynamics para la guía', [
          'shipping_guide_id' => $shippingGuide->id,
          'document_number' => $shippingGuide->document_number
        ]);
        return;
      }

      // Obtener el dyn_series del resultado de Dynamics
      $dynSeriesFromDynamics = isset($result->Serie) ? trim($result->Serie) : null;

      if (empty($dynSeriesFromDynamics)) {
        Log::warning('El resultado de Dynamics no contiene Serie', [
          'shipping_guide_id' => $shippingGuide->id,
          'document_number' => $shippingGuide->document_number
        ]);
        return;
      }

      // Actualizar el dyn_series de la guía
      $shippingGuide->update([
        'dyn_series' => $dynSeriesFromDynamics,
        'status_dynamic' => true,
      ]);

      Log::info('Guía de remisión sincronizada - dyn_series actualizado', [
        'shipping_guide_id' => $shippingGuide->id,
        'document_number' => $shippingGuide->document_number,
        'dyn_series' => $dynSeriesFromDynamics
      ]);

      // Verificar si la transferencia ya está contabilizada en Dynamics
      $isAccounted = isset($result->Contabilizado) ? (bool)$result->Contabilizado : false;

      if (!$isAccounted) {
        Log::info('La transferencia aún no está contabilizada en Dynamics', [
          'shipping_guide_id' => $shippingGuide->id,
          'document_number' => $shippingGuide->document_number
        ]);
        return;
      }

      // Si está contabilizada, procesar el movimiento de inventario
      Log::info('La transferencia está contabilizada, procesando movimiento de inventario', [
        'shipping_guide_id' => $shippingGuide->id,
        'document_number' => $shippingGuide->document_number
      ]);

      // Obtener el TransferReception asociado a la guía
      $transferReception = TransferReception::where('shipping_guide_id', $shippingGuide->id)->first();

      if (!$transferReception) {
        Log::warning('No se encontró la recepción de transferencia asociada', [
          'shipping_guide_id' => $shippingGuide->id,
          'document_number' => $shippingGuide->document_number
        ]);
        return;
      }

      // Obtener el TransferMovement (TRANSFER_OUT)
      $transferOutMovement = $transferReception->transferMovement;

      if (!$transferOutMovement) {
        Log::warning('No se encontró el movimiento de transferencia asociado', [
          'shipping_guide_id' => $shippingGuide->id,
          'transfer_reception_id' => $transferReception->id
        ]);
        return;
      }

      // Ejecutar generateInventoryMovement del servicio
      $transferReceptionService = new TransferReceptionService();
      $transferInMovement = $transferReceptionService->generateInventoryMovement($transferReception, $transferOutMovement);

      Log::info('Movimiento de inventario generado exitosamente', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reception_id' => $transferReception->id,
        'transfer_in_movement_id' => $transferInMovement->id
      ]);

    } catch (\Exception $e) {
      Log::error('Error procesando guía de remisión en Dynamics', [
        'shipping_guide_id' => $shippingGuide->id,
        'document_number' => $shippingGuide->document_number,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Consulta el Procedimiento Almacenado
   */
  protected function consultStoredProcedure(string $documentNumber): ?object
  {
    try {
      // Ejecutar el PA: EXEC neIvConsultarTransferenciasInventario 'PTRA-00000157'
      $results = DB::connection('dbtest')
        ->select("EXEC neIvConsultarTransferenciasInventario '{$documentNumber}'");

      // El PA debería retornar un resultado con el campo Serie (dyn_series)
      if (!empty($results) && isset($results[0])) {
        return $results[0];
      }

      return null;
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarTransferenciasInventario', [
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
