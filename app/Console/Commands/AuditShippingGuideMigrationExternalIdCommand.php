<?php

namespace App\Console\Commands;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog as MLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audita los migration logs de guías de remisión cuyo external_id quedó
 * desalineado del dyn_series actual de la guía (típicamente porque la guía
 * se renumeró después de crear los logs). Ver caso guía 1193 / T603-121990.
 *
 * Por defecto solo reporta. Con --fix corrige el external_id de los logs
 * para que apunte a la serie actual de la guía.
 */
class AuditShippingGuideMigrationExternalIdCommand extends Command
{
  protected $signature = 'shipping-guide:audit-migration-external-id
    {--id=* : Limitar a una o más guías (shipping_guide_id)}
    {--fix : Corregir el external_id de los logs desalineados}
    {--check-dbtp : Consultar la BD intermedia para ver qué serie existe realmente}';

  protected $description = 'Detecta (y opcionalmente corrige) migration logs de guías de remisión con external_id desalineado del dyn_series';

  /** Steps de guías de remisión que llevan external_id = serie de Dynamics. */
  private const GUIDE_STEPS = [
    MLog::STEP_INVENTORY_TRANSFER,
    MLog::STEP_INVENTORY_TRANSFER_DETAIL,
    MLog::STEP_INVENTORY_TRANSFER_SERIAL,
    MLog::STEP_INVENTORY_TRANSFER_REVERSAL,
    MLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL,
    MLog::STEP_INVENTORY_TRANSFER_SERIAL_REVERSAL,
    MLog::STEP_SALE_SHIPPING_GUIDE,
    MLog::STEP_SALE_SHIPPING_GUIDE_DETAIL,
    MLog::STEP_SALE_SHIPPING_GUIDE_SERIAL,
    MLog::STEP_SALE_SHIPPING_GUIDE_REVERSAL,
    MLog::STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL,
    MLog::STEP_SALE_SHIPPING_GUIDE_SERIAL_REVERSAL,
  ];

  public function handle(): int
  {
    $ids = array_filter((array) $this->option('id'));
    $fix = (bool) $this->option('fix');
    $checkDbtp = (bool) $this->option('check-dbtp');

    $logs = MLog::query()
      ->whereNotNull('shipping_guide_id')
      ->whereIn('step', self::GUIDE_STEPS)
      ->when($ids, fn($q) => $q->whereIn('shipping_guide_id', $ids))
      ->orderBy('shipping_guide_id')
      ->get(['id', 'shipping_guide_id', 'step', 'status', 'external_id', 'proceso_estado', 'created_at']);

    if ($logs->isEmpty()) {
      $this->info('No hay migration logs de guías de remisión para revisar.');
      return self::SUCCESS;
    }

    $guides = ShippingGuides::withTrashed()
      ->whereIn('id', $logs->pluck('shipping_guide_id')->unique())
      ->get(['id', 'document_number', 'dyn_series', 'migration_status', 'status_dynamic', 'updated_at'])
      ->keyBy('id');

    // ---- 1) Mismatch external_id vs dyn_series actual ----
    $mismatches = collect();
    foreach ($logs as $log) {
      $guide = $guides->get($log->shipping_guide_id);
      if (!$guide || empty($guide->dyn_series)) {
        continue;
      }
      $expected = $this->expectedExternalId($guide->dyn_series, $log->step);
      $actual = (string) $log->external_id;
      if ($actual === '' || $this->normalize($actual, $log->step) === $this->normalize($expected, $log->step)) {
        continue;
      }
      $mismatches->push(compact('log', 'guide', 'expected', 'actual'));
    }

    // ---- 2) Colisiones: mismo external_id en varias guías ----
    $collisions = $logs
      ->filter(fn($l) => filled($l->external_id))
      ->groupBy('external_id')
      ->filter(fn($g) => $g->pluck('shipping_guide_id')->unique()->count() > 1)
      ->map(fn($g, $ext) => [
        'external_id' => $ext,
        'guides' => $g->pluck('shipping_guide_id')->unique()->sort()->values()->all(),
        'log_ids' => $g->pluck('id')->sort()->values()->all(),
      ])
      ->values();

    $this->info(sprintf('Logs revisados: %d   Guías: %d', $logs->count(), $guides->count()));
    $this->newLine();

    // ---- Reporte mismatch ----
    $this->line("<comment>1) external_id desalineado del dyn_series actual ({$mismatches->count()})</comment>");
    if ($mismatches->isNotEmpty()) {
      $rows = $mismatches->map(fn($m) => [
        $m['log']->id,
        $m['guide']->id,
        $m['guide']->document_number,
        $m['log']->step,
        "{$m['log']->status}/pe={$m['log']->proceso_estado}",
        $m['actual'],
        $m['expected'],
      ])->all();
      $this->table(['log', 'guía', 'documento', 'step', 'estado', 'external_id', 'esperado'], $rows);
    } else {
      $this->line('   (ninguno)');
    }
    $this->newLine();

    // ---- Reporte colisiones ----
    $this->line("<comment>2) Colisiones de external_id entre guías ({$collisions->count()})</comment>");
    foreach ($collisions as $c) {
      $this->line("   {$c['external_id']}  ->  guías " . implode(', ', $c['guides']) . '  (logs ' . implode(', ', $c['log_ids']) . ')');
    }
    if ($collisions->isEmpty()) {
      $this->line('   (ninguna)');
    }
    $this->newLine();

    // ---- 3) Verificación en BD intermedia ----
    if ($checkDbtp && $mismatches->isNotEmpty()) {
      $this->line('<comment>3) ¿Qué serie existe en la BD intermedia (dbtp)?</comment>');
      $headerMismatches = $mismatches->filter(
        fn($m) => in_array($m['log']->step, [MLog::STEP_INVENTORY_TRANSFER, MLog::STEP_SALE_SHIPPING_GUIDE], true)
      );
      foreach ($headerMismatches as $m) {
        [$table, $col] = str_starts_with($m['log']->step, 'sale')
          ? ['neInTbTransaccionInventario', 'TransaccionId']
          : ['neInTbTransferenciaInventario', 'TransferenciaId'];
        try {
          $hasExpected = DB::connection('dbtp')->table($table)->where($col, $m['expected'])->exists();
          $hasActual = DB::connection('dbtp')->table($table)->where($col, $m['actual'])->exists();
          $this->line(sprintf(
            '   Guía %d %s: esperado(%s)=%s  actual(%s)=%s',
            $m['guide']->id,
            $m['guide']->document_number,
            $m['expected'],
            $hasExpected ? 'SÍ' : 'no',
            $m['actual'],
            $hasActual ? 'SÍ' : 'no'
          ));
        } catch (\Throwable $e) {
          $this->warn("   Guía {$m['guide']->id}: error consultando dbtp: {$e->getMessage()}");
        }
      }
      $this->newLine();
    }

    // ---- 4) Fix ----
    if (!$fix) {
      if ($mismatches->isNotEmpty()) {
        $this->comment('Ejecuta con --fix para corregir el external_id de estos logs.');
      }
      return self::SUCCESS;
    }

    if ($mismatches->isEmpty()) {
      $this->info('Nada que corregir.');
      return self::SUCCESS;
    }

    if (!$this->confirm("¿Corregir el external_id de {$mismatches->count()} log(s) para que apunte al dyn_series actual?")) {
      $this->warn('Cancelado.');
      return self::SUCCESS;
    }

    $updated = 0;
    foreach ($mismatches as $m) {
      $m['log']->update(['external_id' => $m['expected']]);
      $this->line("   log {$m['log']->id} (guía {$m['guide']->id}): {$m['actual']} -> {$m['expected']}");
      $updated++;
    }
    $this->info("✓ {$updated} log(s) corregidos.");
    $this->comment('Revisa si estas guías necesitan re-verificación en Dynamics (shipping-guide:verify-migration --id=...).');

    return self::SUCCESS;
  }

  /** Serie esperada para un step: dyn_series, con '*' si el step es de reversión. */
  private function expectedExternalId(string $dynSeries, string $step): string
  {
    $base = rtrim($dynSeries, '*');
    return str_contains($step, 'REVERSAL') ? $base . '*' : $base;
  }

  /** Normaliza para comparar ignorando el sufijo '*' cuando el step no es de reversión. */
  private function normalize(string $value, string $step): string
  {
    return str_contains($step, 'REVERSAL') ? $value : rtrim($value, '*');
  }
}
