<?php

namespace App\Console\Commands;

use App\Http\Resources\Dynamics\AccountingEntryHeaderDynamicsResource;
use App\Http\Services\ap\facturacion\AccountingEntryService;
use App\Jobs\ReverseAccountingEntryJob;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Console\Command;

/**
 * Mapea guías de remisión de VENTA que:
 *   - Ya tuvieron su entrega contabilizada en Dynamics (asiento original enviado,
 *     log STEP_ACCOUNTING_ENTRY_HEADER existente), y
 *   - Fueron anuladas en el sistema (cancel(), cancelled_at IS NOT NULL), pero
 *   - NO tienen el log STEP_ACCOUNTING_ENTRY_HEADER_REVERSAL, es decir, la reversión
 *     del asiento contable nunca se envió a la intermedia (dbtp).
 *
 * Estas son guías "huérfanas contablemente": el diario original sigue vivo en
 * Dynamics aunque la guía/venta ya se anuló localmente.
 */
class ShippingGuidesReversalGapReportCommand extends Command
{
  protected $signature = 'shipping-guides:reversal-gap-report
    {--csv= : Ruta de archivo CSV donde exportar el resultado}
    {--dispatch : Además de reportar, despachar ReverseAccountingEntryJob para las guías listadas}
    {--dry-run : Con --dispatch, solo MOSTRAR qué se enviaría (líneas del asiento invertido), sin despachar el job ni escribir nada}
    {--guide=* : Al usar --dispatch, limitar el envío a estas guías (id). Si se omite, aplica a todas las listadas}
    {--force : Al usar --dispatch, no pedir confirmación interactiva por guía}';

  protected $description = 'Lista guías de venta anuladas cuyo asiento contable original fue enviado a Dynamics pero cuya reversión aún no se ha enviado a la intermedia, y opcionalmente despacha el reenvío (--dispatch)';

  public function handle(AccountingEntryService $accountingService): int
  {
    $guides = ShippingGuides::query()
      ->where('transfer_reason_id', SunatConcepts::TRANSFER_REASON_VENTA)
      ->whereNotNull('cancelled_at')
      ->with([
        'vehicleMovement.vehicle',
        'migrationLogs' => fn($q) => $q->whereIn('step', [
          VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER,
          VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER_REVERSAL,
        ]),
      ])
      ->orderBy('cancelled_at')
      ->get()
      ->filter(function (ShippingGuides $guide) {
        $hasOriginal = $guide->migrationLogs->contains(
          fn($log) => $log->step === VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER
        );

        if (!$hasOriginal) {
          // Nunca se contabilizó: no hay nada que reversar.
          return false;
        }

        $hasReversal = $guide->migrationLogs->contains(
          fn($log) => $log->step === VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER_REVERSAL
        );

        return !$hasReversal;
      });

    if ($guides->isEmpty()) {
      $this->info('No se encontraron guías anuladas con reversión de asiento contable pendiente de envío. ✅');
      return self::SUCCESS;
    }

    $deliveriesByGuideId = ApVehicleDelivery::whereIn('shipping_guide_id', $guides->pluck('id'))
      ->get()
      ->keyBy('shipping_guide_id');

    $rows = $guides->map(function (ShippingGuides $guide) use ($deliveriesByGuideId) {
      $delivery = $deliveriesByGuideId->get($guide->id);
      $referenciaOriginal = $guide->migrationLogs
        ->firstWhere('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER)
        ?->external_id;

      return [
        'guide_id' => $guide->id,
        'vin' => $guide->vehicleMovement?->vehicle?->vin ?? 'N/A',
        'dyn_series' => $guide->dyn_series,
        'referencia_asiento_original' => $referenciaOriginal,
        'cancelled_at' => optional($guide->cancelled_at)->format('Y-m-d H:i'),
        'reversion_inventario_dyn' => $guide->is_annulled ? 'confirmada' : 'pendiente',
        'delivery_status' => $delivery?->status_delivery ?? 'N/A',
        'delivery_is_accounted' => $delivery?->is_accounted ? 'sí' : 'no',
      ];
    });

    $this->table(
      ['ID Guía', 'VIN', 'Serie Dynamics', 'Referencia asiento original', 'Anulada el', 'Reversión inventario', 'Estado entrega', 'Entrega contabilizada'],
      $rows->toArray()
    );

    $this->warn("Total: {$rows->count()} guía(s) con reversión de asiento contable pendiente de envío a Dynamics.");

    if ($csvPath = $this->option('csv')) {
      $handle = fopen($csvPath, 'w');
      fputcsv($handle, array_keys($rows->first()));
      foreach ($rows as $row) {
        fputcsv($handle, $row);
      }
      fclose($handle);
      $this->info("Exportado a: {$csvPath}");
    }

    if ($this->option('dispatch')) {
      $this->dispatchReversals($guides, $accountingService);
    } else {
      $this->line('');
      $this->comment('Sugerencia: agrega --dispatch (o --dispatch --dry-run para solo simular) para reenviar la reversión de estas guías a Dynamics.');
    }

    return self::SUCCESS;
  }

  /**
   * Con --dry-run: solo calcula y muestra qué se enviaría (no escribe nada: no crea logs,
   * no consume número de asiento real, no toca dbtp, no despacha el job).
   * Sin --dry-run: pide confirmación por guía (salvo --force) y despacha ReverseAccountingEntryJob,
   * que SÍ escribe en la intermedia.
   */
  protected function dispatchReversals($guides, AccountingEntryService $accountingService): void
  {
    $onlyGuideIds = array_map('intval', $this->option('guide'));
    $dryRun = (bool) $this->option('dry-run');

    $targets = $onlyGuideIds
      ? $guides->filter(fn(ShippingGuides $g) => in_array($g->id, $onlyGuideIds, true))
      : $guides;

    if ($targets->isEmpty()) {
      $this->warn('Ninguna guía del reporte coincide con --guide indicado(s). No se despachó nada.');
      return;
    }

    if ($dryRun) {
      $this->line('');
      $this->info("[DRY-RUN] Simulando reversión para {$targets->count()} guía(s). No se escribirá nada en Dynamics ni en la base de datos.");

      foreach ($targets as $guide) {
        $this->previewReversalForGuide($guide, $accountingService);
      }

      return;
    }

    $this->line('');
    $this->info("Se despachará ReverseAccountingEntryJob para {$targets->count()} guía(s):");

    foreach ($targets as $guide) {
      $vin = $guide->vehicleMovement?->vehicle?->vin ?? 'N/A';

      $confirmed = $this->option('force') || $this->confirm(
        "¿Reenviar reversión del asiento para guía #{$guide->id} ({$guide->document_number}, VIN {$vin})?",
        false
      );

      if (!$confirmed) {
        $this->line("  - Guía #{$guide->id}: omitida.");
        continue;
      }

      ReverseAccountingEntryJob::dispatch($guide->id);
      $this->info("  - Guía #{$guide->id}: despachada a la cola (electronic_documents).");
    }

    $this->line('');
    $this->comment('El job corre en la cola; revisa ap_vehicle_purchase_order_migration_log (step accounting_entry_header_REVERSAL) o vuelve a correr este mismo reporte en unos minutos para confirmar.');
  }

  /**
   * Replica (solo lectura) la lógica de ReverseAccountingEntryJob para una guía: ubica la
   * factura original vía Referencia e invierte Débito/Crédito, pero sin generar número de
   * asiento real, sin crear logs y sin tocar dbtp.
   */
  protected function previewReversalForGuide(ShippingGuides $guide, AccountingEntryService $accountingService): void
  {
    $this->line('');
    $this->line("--- Guía #{$guide->id} ({$guide->document_number}) ---");

    $originalHeaderLog = $guide->migrationLogs
      ->firstWhere('step', VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER);

    if (!$originalHeaderLog) {
      $this->error('  No tiene log de asiento original; no hay nada que reversar.');
      return;
    }

    $vin = $guide->vehicleMovement?->vehicle?->vin;
    if (!$vin) {
      $this->error('  No se pudo determinar el VIN de la guía.');
      return;
    }

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
      ->whereHas('vehicle', fn($q) => $q->where('vin', $vin))
      ->get();

    $electronicDocument = $candidateDocuments->first(
      fn($inv) => AccountingEntryHeaderDynamicsResource::buildReferencia($inv->full_number, $vin) === $originalHeaderLog->external_id
    );

    if (!$electronicDocument) {
      $this->error("  No se pudo ubicar la factura original (Referencia: {$originalHeaderLog->external_id}).");
      return;
    }

    try {
      $reversedLines = array_map(function (array $line) {
        return array_merge($line, [
          'Debito' => $line['Credito'],
          'Credito' => $line['Debito'],
          // Descripcion es varchar(30) NOT NULL en Dynamics: prefijo corto + truncado defensivo.
          'Descripcion' => substr('REV: ' . $line['Descripcion'], 0, 30),
        ]);
      }, $accountingService->generateAccountingLines($electronicDocument, 0));

      $accountingService->validateBalance($reversedLines);
    } catch (\Throwable $e) {
      $this->error("  Error generando las líneas de reversión: {$e->getMessage()}");
      return;
    }

    $reversalReferencia = substr($originalHeaderLog->external_id, 0, 30 - strlen('-REV')) . '-REV';

    try {
      $fecha = $guide->cancelled_at ?? now();
      $headerResource = new AccountingEntryHeaderDynamicsResource($electronicDocument, $fecha, 0);
      $headerData = $headerResource->toArray(request());
      $headerData['Referencia'] = $reversalReferencia;
    } catch (\Throwable $e) {
      $this->error("  Error generando la cabecera de reversión: {$e->getMessage()}");
      return;
    }

    $this->line("  Factura origen: {$electronicDocument->full_number}");
    $this->line("  Referencia original: {$originalHeaderLog->external_id}");
    $this->line("  Referencia de reversión: {$reversalReferencia}");
    $this->line('  Cabecera (neInTbIntegracionAsientoCab) — "Asiento"=0 es placeholder, no se consume número real:');

    $this->table(
      array_keys($headerData),
      [array_values($headerData)]
    );

    $this->line('  Detalle (neInTbIntegracionAsientoDet):');

    $this->table(
      ['Línea', 'Cuenta', 'Débito', 'Crédito', 'Descripción'],
      array_map(fn($l) => [$l['Linea'], $l['CuentaNumero'], $l['Debito'], $l['Credito'], $l['Descripcion']], $reversedLines)
    );
  }
}
