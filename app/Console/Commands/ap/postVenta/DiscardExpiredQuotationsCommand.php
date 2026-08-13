<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\ApMasters;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DiscardExpiredQuotationsCommand extends Command
{
  protected $signature = 'quotations:discard-expired
                          {--dry-run : Mostrar qué se descartaría sin realizar cambios}';

  protected $description = 'Descarta automáticamente las cotizaciones de mesón cuya expiration_date ya pasó';

  public function handle(): int
  {
    $dryRun = $this->option('dry-run');
    $now = Carbon::now('America/Lima');

    // Buscar cotizaciones expiradas con los filtros especificados
    $expiredQuotations = ApOrderQuotations::where('area_id', ApMasters::AREA_MESON)
      ->whereIn('status_id', [
        ApMasters::STATUS_ORDER_QUOTE_APERTURADO,
        ApMasters::STATUS_ORDER_QUOTE_APROBADO
      ])
      ->where('expiration_date', '<', $now)
      ->get();

    if ($expiredQuotations->isEmpty()) {
      $this->info('No hay cotizaciones de mesón vencidas para descartar.');
      return 0;
    }

    $this->info("Cotizaciones vencidas encontradas: {$expiredQuotations->count()}");

    $discarded = 0;
    $skipped = 0;

    foreach ($expiredQuotations as $quotation) {
      // Verificar que NO tenga ningún tipo de pago
      if ($this->hasAnyPayment($quotation)) {
        $this->line("  - [OMITIDO] {$quotation->quotation_number} - Tiene pagos asociados");
        $skipped++;
        continue;
      }

      $statusName = $quotation->status_id === ApMasters::STATUS_ORDER_QUOTE_APERTURADO ? 'APERTURADO' : 'APROBADO';
      $this->line("  - [{$quotation->quotation_number}] Estado: {$statusName}, Expiró: {$quotation->expiration_date}");

      if ($dryRun) {
        continue;
      }

      // Cambiar estado a DESCARTADO
      $quotation->status_id = ApMasters::STATUS_ORDER_QUOTE_DESCARTADO;
      $quotation->discard_reason_id = ApMasters::STATUS_ORDER_QUOTE_ANULADO;
      $quotation->discarded_at = $now;
      $quotation->discarded_note = 'Descartado automáticamente por SIAN.';
      $quotation->save();
      $discarded++;
    }

    if ($dryRun) {
      $this->warn('Modo dry-run: no se realizaron cambios.');
      $this->info("Se descartarían {$expiredQuotations->count()} cotización(es).");
      if ($skipped > 0) {
        $this->info("Se omitirían {$skipped} cotización(es) por tener pagos asociados.");
      }
    } else {
      $this->info("Se descartaron {$discarded} cotización(es) correctamente.");
      if ($skipped > 0) {
        $this->warn("Se omitieron {$skipped} cotización(es) por tener pagos asociados.");
      }
    }

    return 0;
  }

  /**
   * Verifica si la cotización tiene algún tipo de pago
   * (anticipos o factura final, ya sea en borrador, enviado o aceptado)
   */
  private function hasAnyPayment(ApOrderQuotations $quotation): bool
  {
    return $quotation->hasDraftAdvance()
      || $quotation->hasDraftFinalInvoice()
      || $quotation->hasAdvances()
      || $quotation->hasFinalInvoice();
  }
}
