<?php

namespace App\Console\Commands\ap;

use App\Jobs\RedistributePotentialBuyersJob;
use App\Models\ap\comercial\PotentialBuyers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RedistributePotentialBuyersCommand extends Command
{
  protected $signature = 'ap:redistribute-potential-buyers
                          {--detail  : Muestra tabla comparativa antes/después por cada grupo}
                          {--queue   : Despacha el job a la cola en lugar de ejecutar en consola}';

  protected $description = 'Redistribuye leads pendientes (use=0 y >24h) entre asesores del mismo grupo shop+marca';

  public function handle(): int
  {
    if ($this->option('queue')) {
      $this->info('Despachando redistribución a la cola...');
      RedistributePotentialBuyersJob::dispatch();
      $this->info('Job despachado.');
      return CommandAlias::SUCCESS;
    }

    $year  = now()->year;
    $month = now()->month;

    $this->info("=== Redistribución de leads pendientes | {$month}/{$year} ===");
    $this->newLine();

    $groups = DB::table('ap_assign_brand_consultant as abc')
      ->join('config_sede as cs', 'cs.id', '=', 'abc.sede_id')
      ->where('abc.year', $year)
      ->where('abc.month', $month)
      ->where('abc.status', 1)
      ->whereNull('abc.deleted_at')
      ->whereNotNull('cs.shop_id')
      ->select('cs.shop_id', 'abc.brand_id')
      ->distinct()
      ->get();

    if ($groups->isEmpty()) {
      $this->warn('No hay grupos activos para este mes.');
      return CommandAlias::SUCCESS;
    }

    $summaryRows      = [];
    $totalTransferred = 0;

    foreach ($groups as $group) {
      $result = $this->processGroup($group->shop_id, $group->brand_id, $year, $month);

      $summaryRows[] = [
        $result['shop'],
        $result['brand'],
        $result['workers'],
        $result['total'],
        $result['transferred'],
        $result['floor'] > 0 ? $result['floor'] : '—',
        $result['status'],
      ];

      $totalTransferred += $result['transferred'];
    }

    // Tabla resumen siempre visible
    $this->table(
      ['Shop', 'Marca', 'Asesores', 'Leads (>24h)', 'Reasignados', 'Objetivo c/u', 'Estado'],
      $summaryRows
    );

    $this->newLine();
    $this->info("Total leads reasignados: <fg=green;options=bold>{$totalTransferred}</>");

    return CommandAlias::SUCCESS;
  }

  private function processGroup(int $shopId, int $brandId, int $year, int $month): array
  {
    $shopName  = DB::table('ap_masters')->where('id', $shopId)->value('description') ?? "Shop #{$shopId}";
    $brandName = DB::table('ap_vehicle_brand')->where('id', $brandId)->value('name') ?? "Marca #{$brandId}";

    $base = [
      'shop'        => $shopName,
      'brand'       => $brandName,
      'workers'     => 0,
      'total'       => 0,
      'transferred' => 0,
      'floor'       => 0,
      'status'      => '<fg=yellow>omitido</>',
      'before'      => [],
      'after'       => [],
      'worker_names'=> [],
    ];

    // Asesores activos
    $workerIds = DB::table('ap_assign_brand_consultant as abc')
      ->join('config_sede as cs', 'cs.id', '=', 'abc.sede_id')
      ->where('cs.shop_id', $shopId)
      ->where('abc.brand_id', $brandId)
      ->where('abc.year', $year)
      ->where('abc.month', $month)
      ->where('abc.status', 1)
      ->whereNull('abc.deleted_at')
      ->pluck('abc.worker_id')
      ->unique()
      ->toArray();

    $base['workers'] = count($workerIds);

    if (count($workerIds) < 2) {
      $base['status'] = '<fg=yellow>< 2 asesores</>';
      $this->printDetail($base, []);
      return $base;
    }

    $workerNames = DB::table('rrhh_persona')
      ->whereIn('id', $workerIds)
      ->pluck('nombre_completo', 'id');

    $base['worker_names'] = $workerNames;

    // Leads pendientes >24h
    $pendingLeads = PotentialBuyers::join('config_sede as cs', 'cs.id', '=', 'potential_buyers.sede_id')
      ->where('cs.shop_id', $shopId)
      ->where('potential_buyers.vehicle_brand_id', $brandId)
      ->where('potential_buyers.use', PotentialBuyers::CREATED)
      ->whereIn('potential_buyers.worker_id', $workerIds)
      ->where('potential_buyers.created_at', '<=', now()->subHours(24))
      ->whereNull('potential_buyers.deleted_at')
      ->orderBy('potential_buyers.created_at')
      ->get(['potential_buyers.id', 'potential_buyers.worker_id']);

    $workerLeads = array_fill_keys($workerIds, []);
    foreach ($pendingLeads as $lead) {
      if (array_key_exists($lead->worker_id, $workerLeads)) {
        $workerLeads[$lead->worker_id][] = $lead->id;
      }
    }

    $total = $pendingLeads->count();
    $floor = (int) floor($total / count($workerIds));
    $base['total'] = $total;
    $base['floor'] = $floor;

    // Estado ANTES para --detail
    $base['before'] = array_map(fn($ids) => count($ids), $workerLeads);

    if ($total === 0) {
      $base['status'] = '<fg=green>sin pendientes</>';
      $this->printDetail($base, $workerIds);
      return $base;
    }

    if ($floor === 0) {
      $base['status'] = '<fg=yellow>leads < asesores</>';
      $this->printDetail($base, $workerIds);
      return $base;
    }

    $donors    = [];
    $receivers = [];
    foreach ($workerIds as $wid) {
      $cnt = count($workerLeads[$wid]);
      if ($cnt > $floor) {
        $donors[] = ['worker_id' => $wid, 'surplus_ids' => array_slice($workerLeads[$wid], $floor)];
      } elseif ($cnt < $floor) {
        $receivers[] = ['worker_id' => $wid, 'deficit' => $floor - $cnt];
      }
    }

    if (empty($donors) || empty($receivers)) {
      $base['status'] = '<fg=green>equilibrado</>';
      $base['after']  = $base['before'];
      $this->printDetail($base, $workerIds);
      return $base;
    }

    // Ejecutar redistribución
    $transferred = 0;
    $afterCounts = $base['before'];

    DB::beginTransaction();
    try {
      $di = 0;
      $sp = 0;
      foreach ($receivers as $receiver) {
        $needed        = $receiver['deficit'];
        $leadsToAssign = [];

        while ($needed > 0 && $di < count($donors)) {
          $available = count($donors[$di]['surplus_ids']) - $sp;
          if ($available <= 0) { $di++; $sp = 0; continue; }
          $take          = min($needed, $available);
          $leadsToAssign = array_merge($leadsToAssign, array_slice($donors[$di]['surplus_ids'], $sp, $take));
          $afterCounts[$receiver['worker_id']]      += $take;
          $afterCounts[$donors[$di]['worker_id']]   -= $take;
          $sp     += $take;
          $needed -= $take;
        }

        if (!empty($leadsToAssign)) {
          PotentialBuyers::whereIn('id', $leadsToAssign)
            ->update(['worker_id' => $receiver['worker_id']]);
          $transferred += count($leadsToAssign);
        }
      }
      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      $base['status'] = "<fg=red>error: {$e->getMessage()}</>";
      $this->printDetail($base, $workerIds);
      return $base;
    }

    $base['transferred'] = $transferred;
    $base['after']       = $afterCounts;
    $base['status']      = "<fg=green>✓ {$transferred} movidos</>";

    $this->printDetail($base, $workerIds);

    return $base;
  }

  private function printDetail(array $result, array $workerIds): void
  {
    if (!$this->option('detail')) {
      return;
    }

    $this->line("─────────────────────────────────────────────────────────────");
    $this->line("  Shop: <fg=cyan>{$result['shop']}</> | Marca: <fg=cyan>{$result['brand']}</>");
    $this->line("─────────────────────────────────────────────────────────────");

    if (empty($workerIds) || count($workerIds) < 2) {
      $this->line("  <fg=yellow>{$result['status']}</>");
      $this->newLine();
      return;
    }

    $rows = [];
    foreach ($workerIds as $wid) {
      $name   = $result['worker_names'][$wid] ?? "Worker #{$wid}";
      $before = $result['before'][$wid] ?? 0;
      $after  = $result['after'][$wid]  ?? $before;
      $diff   = $after - $before;

      $diffStr = match(true) {
        $diff > 0 => "<fg=green>+{$diff}</>",
        $diff < 0 => "<fg=red>{$diff}</>",
        default   => '<fg=gray>0</>',
      };

      $rows[] = [$name, $before, $after, $diffStr];
    }

    $this->table(['Asesor', 'Antes', 'Después', 'Δ'], $rows);
    $this->line("  {$result['status']} | Objetivo: {$result['floor']} leads/asesor");
    $this->newLine();
  }
}
