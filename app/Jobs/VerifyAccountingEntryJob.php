<?php

namespace App\Jobs;

use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifica si un asiento contable ya fue procesado por GP (Estado=1 en neInTbIntegracionAsientoCab),
 * confirma su existencia en GL20000_GL30000 y marca ap_vehicle_delivery como completado.
 *
 * FLUJO:
 *   1. Busca logs accounting_entry_header con status=completed y proceso_estado=0 (insertados, aún no confirmados por GP).
 *   2. Consulta neInTbIntegracionAsientoCab por Referencia (= external_id del log).
 *   3. Si Estado=1, extrae JRNENTRY del campo Error ("OK, Asiento GP: 229, Lote: ...").
 *   4. Consulta GL20000_GL30000 WHERE JRNENTRY = X en dbdp2 para confirmar contabilización.
 *   5. Si existe → actualiza proceso_estado=1 en ambos logs de asiento y status_delivery='completed' en ap_vehicle_delivery.
 *
 * COLA: electronic_documents | tries: 3 | timeout: 120s | backoff: 60s
 */
class VerifyAccountingEntryJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 120;
  public int $backoff = 60;

  public function __construct(public ?int $shippingGuideId = null)
  {
    $this->onQueue('electronic_documents');
  }

  public function handle(): void
  {
    if ($this->shippingGuideId) {
      $this->processShippingGuide($this->shippingGuideId);
    } else {
      $this->processAllPending();
    }
  }

  protected function processAllPending(): void
  {
    // Solo guías que ya migraron completamente a Dynamics Y tienen asiento enviado pendiente de confirmación GP
    $pendingLogs = VehiclePurchaseOrderMigrationLog::where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER)
      ->where('status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where('proceso_estado', 0)
      ->where('attempts', '<', 3)
      ->whereNotNull('shipping_guide_id')
      ->whereHas('shippingGuide', function ($q) {
        $q->where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
          ->where('status_dynamic', 1);
      })
      ->get();

    foreach ($pendingLogs as $log) {
      try {
        $this->processShippingGuide($log->shipping_guide_id);
      } catch (Exception $e) {
        Log::error('VerifyAccountingEntryJob: error procesando guía', [
          'shipping_guide_id' => $log->shipping_guide_id,
          'error' => $e->getMessage(),
        ]);
        continue;
      }
    }
  }

  protected function processShippingGuide(int $shippingGuideId): void
  {
    $headerLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuideId)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER)
      ->where('status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where('proceso_estado', 0)
      ->first();

    if (!$headerLog) {
      return;
    }

    // Registrar intento de consulta a la intermedia
    $headerLog->update([
      'attempts' => $headerLog->attempts + 1,
      'last_attempt_at' => now(),
    ]);

    $asientoRecord = DB::connection('dbtp')
      ->table('neInTbIntegracionAsientoCab')
      ->where('Referencia', $headerLog->external_id)
      ->first();

    if (!$asientoRecord) {
      Log::warning('VerifyAccountingEntryJob: registro no encontrado en tabla intermedia', [
        'shipping_guide_id' => $shippingGuideId,
        'referencia' => $headerLog->external_id,
        'intento' => $headerLog->attempts,
      ]);
      return;
    }

    if ((int) $asientoRecord->Estado !== 1) {
      Log::info('VerifyAccountingEntryJob: asiento aún no procesado por GP', [
        'shipping_guide_id' => $shippingGuideId,
        'referencia' => $headerLog->external_id,
        'estado' => $asientoRecord->Estado,
        'intento' => $headerLog->attempts,
      ]);
      return;
    }

    $jrnEntry = $this->extractJrnEntry($asientoRecord->Error ?? '');

    if (!$jrnEntry) {
      Log::error('VerifyAccountingEntryJob: no se pudo extraer JRNENTRY del campo Error', [
        'shipping_guide_id' => $shippingGuideId,
        'error_field' => $asientoRecord->Error,
      ]);
      return;
    }

    $glRecord = DB::connection('dbdp2')
      ->table('GL20000_GL30000')
      ->where('JRNENTRY', $jrnEntry)
      ->first();

    if (!$glRecord) {
      Log::info('VerifyAccountingEntryJob: JRNENTRY aún no aparece en GL20000_GL30000', [
        'shipping_guide_id' => $shippingGuideId,
        'jrn_entry' => $jrnEntry,
      ]);
      return;
    }

    $this->completeAccountingEntry($shippingGuideId, $headerLog);

    Log::info('VerifyAccountingEntryJob: asiento confirmado en GL, entrega marcada como completada', [
      'shipping_guide_id' => $shippingGuideId,
      'jrn_entry' => $jrnEntry,
      'referencia' => $headerLog->external_id,
    ]);
  }

  protected function extractJrnEntry(string $errorField): ?int
  {
    // Formato esperado: "OK, Asiento GP: 229, Lote: 41037767"
    if (preg_match('/Asiento\s+GP:\s*(\d+)/i', $errorField, $matches)) {
      return (int) $matches[1];
    }
    return null;
  }

  protected function completeAccountingEntry(int $shippingGuideId, VehiclePurchaseOrderMigrationLog $headerLog): void
  {
    $headerLog->updateProcesoEstado(1);

    VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuideId)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL)
      ->where('status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where('proceso_estado', 0)
      ->update([
        'proceso_estado' => 1,
        'completed_at' => now(),
        'last_attempt_at' => now(),
      ]);

    ApVehicleDelivery::where('shipping_guide_id', $shippingGuideId)
      ->where('status_delivery', '!=', 'completed')
      ->update(['status_delivery' => 'completed']);
  }

  public function failed(Throwable $exception): void
  {
    Log::error('VerifyAccountingEntryJob falló definitivamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
    ]);
  }
}
