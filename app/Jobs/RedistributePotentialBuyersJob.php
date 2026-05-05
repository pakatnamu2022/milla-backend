<?php

namespace App\Jobs;

use App\Models\ap\comercial\PotentialBuyers;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedistributePotentialBuyersJob implements ShouldQueue
{
  use Queueable, InteractsWithQueue, SerializesModels;

  public $tries = 1;
  public $timeout = 300;

  public function handle(): void
  {
    $year  = now()->year;
    $month = now()->month;

    // Grupos únicos (shop_id, brand_id) activos del mes
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

    foreach ($groups as $group) {
      $this->processGroup($group->shop_id, $group->brand_id, $year, $month);
    }
  }

  private function processGroup(int $shopId, int $brandId, int $year, int $month): void
  {
    DB::beginTransaction();
    try {
      // Asesores activos en cualquier sede de este shop para esta marca
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

      if (count($workerIds) < 2) {
        DB::commit();
        return;
      }

      // Leads pendientes (use=0, >24h) de todas las sedes del shop para esta marca
      $pendingLeads = PotentialBuyers::join('config_sede as cs', 'cs.id', '=', 'potential_buyers.sede_id')
        ->where('cs.shop_id', $shopId)
        ->where('potential_buyers.vehicle_brand_id', $brandId)
        ->where('potential_buyers.use', PotentialBuyers::CREATED)
        ->whereIn('potential_buyers.worker_id', $workerIds)
        ->where('potential_buyers.created_at', '<=', now()->subHours(24))
        ->whereNull('potential_buyers.deleted_at')
        ->orderBy('potential_buyers.created_at')
        ->get(['potential_buyers.id', 'potential_buyers.worker_id']);

      if ($pendingLeads->isEmpty()) {
        DB::commit();
        return;
      }

      // Agrupar IDs por worker
      $workerLeads = array_fill_keys($workerIds, []);
      foreach ($pendingLeads as $lead) {
        if (array_key_exists($lead->worker_id, $workerLeads)) {
          $workerLeads[$lead->worker_id][] = $lead->id;
        }
      }

      $total = $pendingLeads->count();
      $floor = (int) floor($total / count($workerIds));

      if ($floor === 0) {
        DB::commit();
        return;
      }

      // Clasificar donantes (count > floor) y receptores (count < floor)
      $donors    = [];
      $receivers = [];
      foreach ($workerIds as $wid) {
        $cnt = count($workerLeads[$wid]);
        if ($cnt > $floor) {
          $donors[] = [
            'worker_id'   => $wid,
            'surplus_ids' => array_slice($workerLeads[$wid], $floor),
          ];
        } elseif ($cnt < $floor) {
          $receivers[] = ['worker_id' => $wid, 'deficit' => $floor - $cnt];
        }
      }

      if (empty($donors) || empty($receivers)) {
        DB::commit();
        return;
      }

      // Redistribuir leads de donantes a receptores
      $di = 0;
      $sp = 0;
      foreach ($receivers as $receiver) {
        $needed        = $receiver['deficit'];
        $leadsToAssign = [];

        while ($needed > 0 && $di < count($donors)) {
          $available = count($donors[$di]['surplus_ids']) - $sp;
          if ($available <= 0) {
            $di++;
            $sp = 0;
            continue;
          }
          $take          = min($needed, $available);
          $leadsToAssign = array_merge(
            $leadsToAssign,
            array_slice($donors[$di]['surplus_ids'], $sp, $take)
          );
          $sp     += $take;
          $needed -= $take;
        }

        if (!empty($leadsToAssign)) {
          PotentialBuyers::whereIn('id', $leadsToAssign)
            ->update(['worker_id' => $receiver['worker_id']]);
        }
      }

      DB::commit();

      Log::info('RedistributePotentialBuyersJob: grupo procesado', [
        'shop_id'  => $shopId,
        'brand_id' => $brandId,
        'total'    => $total,
        'floor'    => $floor,
      ]);

    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('RedistributePotentialBuyersJob: error en grupo', [
        'shop_id'  => $shopId,
        'brand_id' => $brandId,
        'error'    => $e->getMessage(),
      ]);
    }
  }

  public function failed(\Throwable $e): void
  {
    Log::error('RedistributePotentialBuyersJob: fallo general', [
      'error' => $e->getMessage(),
    ]);
  }
}
