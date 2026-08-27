<?php

namespace App\Console\Commands\ap;

use App\Http\Services\ap\comercial\PurchaseRequestQuoteService;
use App\Models\ap\comercial\DiscountCoupons;
use App\Models\ap\comercial\PurchaseRequestQuote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detecta bonos/descuentos FIJO cuyo `amount` fue reducido por el factor de
 * retención 7% (x0.93) MÁS DE UNA VEZ, por el bug de doble aplicación
 * (front mandaba el neto + backend volvía a aplicar el 0.93 en cada guardado).
 *
 * Heurística: el valor BRUTO original casi siempre es "redondo" (múltiplo de
 * 10; en su defecto de 5, o entero). Se divide `amount` entre 0.93^k hasta
 * caer en un bruto redondo:
 *   k = 0  -> amount ya es redondo            -> sin retención, correcto
 *   k = 1  -> amount = bruto * 0.93           -> retención aplicada 1 vez, correcto
 *   k >= 2 -> se aplicó (k-1) veces de más    -> corregir a amount / 0.93^(k-1)
 *
 * Solo compone el error `type = FIJO`: PORCENTAJE recarga desde `percentage`
 * (se guarda crudo) y nunca se compone.
 *
 * Uso:
 *   php artisan ap:diagnose-retention-discount            # solo reporte
 *   php artisan ap:diagnose-retention-discount --fix      # corrige montos
 *   php artisan ap:diagnose-retention-discount --fix --recalc-margin
 *   php artisan ap:diagnose-retention-discount --tol=0.05 --max-k=4
 */
class DiagnoseRetentionDiscountCommand extends Command
{
  protected $signature = 'ap:diagnose-retention-discount
    {--fix : Corrige el amount/precio_unitario/valor_unitario de los cupones con doble+ aplicación}
    {--recalc-margin : Tras --fix, recalcula el margen de las cotizaciones afectadas}
    {--tol=0.02 : Tolerancia absoluta para considerar un bruto como "redondo"}
    {--max-k=4 : Máximo de divisiones por 0.93 a probar}
    {--units=10 : Divisores que cuentan como "redondo" para el bruto, coma-separados (ej. 10,50,25)}
    {--legacy : Incluye también has_retention=0 (era previa al flag). Ambiguo: NO se corrige, solo se lista}
    {--include-flag-mismatch : Lista también los FIJO con has_retention=0 pero amount = bruto*0.93 (informativo, nunca se corrige)}';

  protected $description = 'Diagnostica (y opcionalmente corrige) bonos FIJO con la retención 7% aplicada más de una vez';

  private const FACTOR = 0.93;

  public function handle(PurchaseRequestQuoteService $quoteService): int
  {
    $tol = (float) $this->option('tol');
    $maxK = (int) $this->option('max-k');
    $doFix = (bool) $this->option('fix');
    $units = array_values(array_filter(array_map(
      'intval',
      explode(',', (string) $this->option('units'))
    ), fn ($u) => $u > 0)) ?: [10];

    $query = DB::table('discount_coupons as dc')
      ->join('purchase_request_quote as prq', 'prq.id', '=', 'dc.purchase_request_quote_id')
      ->whereNull('dc.deleted_at')
      ->where('dc.type', 'FIJO')
      ->where('dc.amount', '>', 0);

    // El bug de doble aplicación solo está activo cuando has_retention = 1
    // (front manda el neto + backend re-aplica 0.93). Las filas con
    // has_retention = 0 son de la era previa al flag: su monto suele ser
    // correcto (o proviene de conversión de moneda) y es ambiguo -> no se
    // corrige, solo se lista con --legacy.
    if (! $this->option('legacy')) {
      $query->where('dc.has_retention', 1);
    }

    $rows = $query->get([
        'dc.id',
        'dc.amount',
        'dc.has_retention',
        'dc.is_negative',
        'dc.purchase_request_quote_id',
        'prq.correlative',
        'prq.is_invoiced',
      ]);

    $compounded = [];      // k >= 2, has_retention = 1  -> a corregir
    $legacySuspect = [];   // k >= 2, has_retention = 0  -> ambiguo, no se corrige
    $flagMismatch = [];    // k == 1 pero has_retention = 0
    $review = [];          // sin k limpio

    foreach ($rows as $r) {
      $amount = (float) $r->amount;
      $k = $this->firstCleanK($amount, $maxK, $tol, $units);

      if ($k === null) {
        $review[] = $r;
        continue;
      }
      if ($k >= 2) {
        $over = $k - 1;
        $entry = [
          'row' => $r,
          'over' => $over,
          'suggested' => round($amount / pow(self::FACTOR, $over), 2),
        ];
        if ($r->has_retention) {
          $compounded[] = $entry;
        } else {
          $legacySuspect[] = $entry;
        }
        continue;
      }
      if ($k === 1 && ! $r->has_retention) {
        $flagMismatch[] = $r;
      }
    }

    $this->info(sprintf(
      'FIJO revisados: %d  |  doble+ (has_retention=1, a corregir): %d  |  sospechosos legacy: %d  |  flag mismatch: %d  |  revisar manual: %d',
      $rows->count(), count($compounded), count($legacySuspect), count($flagMismatch), count($review)
    ));

    // ---- Doble+ aplicación -------------------------------------------------
    if ($compounded) {
      $this->newLine();
      $this->line('<comment>== Cupones con retención aplicada de más ==</comment>');
      $this->table(
        ['coupon_id', 'cotización', 'facturada', 'has_ret', 'amount_actual', 'veces_de_mas', 'amount_sugerido'],
        collect($compounded)->map(fn ($c) => [
          $c['row']->id,
          $c['row']->correlative,
          $c['row']->is_invoiced ? 'SÍ' : 'no',
          $c['row']->has_retention ? '1' : '0',
          number_format((float) $c['row']->amount, 2),
          $c['over'],
          number_format($c['suggested'], 2),
        ])->all()
      );
    }

    if ($legacySuspect) {
      $this->newLine();
      $this->line('<comment>== Sospechosos legacy (has_retention=0): AMBIGUO, revisar caso por caso, NO se corrige ==</comment>');
      $this->table(
        ['coupon_id', 'cotización', 'facturada', 'amount_actual', 'veces_de_mas', 'amount_sugerido'],
        collect($legacySuspect)->map(fn ($c) => [
          $c['row']->id,
          $c['row']->correlative,
          $c['row']->is_invoiced ? 'SÍ' : 'no',
          number_format((float) $c['row']->amount, 2),
          $c['over'],
          number_format($c['suggested'], 2),
        ])->all()
      );
    }

    if ($review) {
      $this->newLine();
      $this->line('<comment>== Sin bruto redondo: revisar a mano ==</comment>');
      $this->table(
        ['coupon_id', 'cotización', 'facturada', 'has_ret', 'amount', 'amount/0.93', 'amount/0.93^2'],
        collect($review)->map(fn ($r) => [
          $r->id,
          $r->correlative,
          $r->is_invoiced ? 'SÍ' : 'no',
          $r->has_retention ? '1' : '0',
          number_format((float) $r->amount, 2),
          number_format((float) $r->amount / self::FACTOR, 2),
          number_format((float) $r->amount / (self::FACTOR * self::FACTOR), 2),
        ])->all()
      );
    }

    if ($this->option('include-flag-mismatch') && $flagMismatch) {
      $this->newLine();
      $this->line('<comment>== has_retention=0 pero amount = bruto*0.93 (SOLO informativo, el monto es correcto) ==</comment>');
      $this->table(
        ['coupon_id', 'cotización', 'amount', 'bruto_implícito'],
        collect($flagMismatch)->map(fn ($r) => [
          $r->id,
          $r->correlative,
          number_format((float) $r->amount, 2),
          number_format((float) $r->amount / self::FACTOR, 2),
        ])->all()
      );
    }

    // ---- Corrección ------------------------------------------------------
    if (! $doFix) {
      if ($compounded) {
        $this->newLine();
        $this->line('Ejecuta con <info>--fix</info> para corregir los ' . count($compounded) . ' cupones.');
      }
      return self::SUCCESS;
    }

    if (! $compounded) {
      $this->info('Nada que corregir.');
      return self::SUCCESS;
    }

    if (! $this->confirm('¿Corregir ' . count($compounded) . ' cupones ahora?', true)) {
      return self::SUCCESS;
    }

    $quoteIds = [];
    DB::transaction(function () use ($compounded, &$quoteIds) {
      foreach ($compounded as $c) {
        $suggested = $c['suggested'];
        DiscountCoupons::whereKey($c['row']->id)->update([
          'amount' => $suggested,
          'precio_unitario' => $suggested,
          'valor_unitario' => round($suggested / 1.18, 4),
          'updated_at' => now(),
        ]);
        $quoteIds[$c['row']->purchase_request_quote_id] = true;
      }
    });
    $this->info('Corregidos ' . count($compounded) . ' cupones en ' . count($quoteIds) . ' cotizaciones.');

    if ($this->option('recalc-margin')) {
      foreach (array_keys($quoteIds) as $qid) {
        $quote = PurchaseRequestQuote::find($qid);
        if ($quote) {
          $quoteService->refreshMargin($quote);
          $this->line("  margen recalculado: cotización id {$qid}");
        }
      }
    } else {
      $this->warn('Recuerda recalcular el margen de las cotizaciones: ' . implode(', ', array_keys($quoteIds)));
    }

    return self::SUCCESS;
  }

  /**
   * Devuelve el menor k en [0, maxK] tal que amount / 0.93^k cae en un
   * múltiplo de alguno de los divisores dados (probados de mayor a menor).
   * null si ninguno lo hace. No usa el nivel "entero suelto" a propósito:
   * a k alto genera falsos positivos por pura coincidencia decimal.
   */
  private function firstCleanK(float $amount, int $maxK, float $tol, array $units): ?int
  {
    rsort($units);
    foreach ($units as $unit) {
      for ($k = 0; $k <= $maxK; $k++) {
        $base = $amount / pow(self::FACTOR, $k);
        if (abs($base - round($base / $unit) * $unit) <= $tol) {
          return $k;
        }
      }
    }
    return null;
  }
}
