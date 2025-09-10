<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignmentLeadershipResource;
use App\Http\Services\BaseService;
use App\Http\Utils\Constants;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadershipPeriod;
use Illuminate\Http\Request;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;

class ApAssignmentLeadershipService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Worker::with('boss')->fromEmpresa(Constants::COMPANY_AP),
      $request,
      Worker::filters,
      Worker::sorts,
      ApAssignmentLeadershipResource::class
    );
  }

  public function listRecord(Request $request)
  {
    $year = $request->query('year');
    $month = $request->query('month');
    $boss = $request->query('boss_name');

    $query = ApAssignmentLeadershipPeriod::with(['boss', 'worker'])
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($boss, function ($q) use ($boss) {
        $q->whereHas('boss', function ($sub) use ($boss) {
          $sub->where('nombre_completo', 'like', "%{$boss}%");
        });
      });

    $periodos = $query->get();

    $grouped = $periodos->groupBy('boss_id')->map(function ($items) {
      $first = $items->first();

      return [
        'boss_id' => $first->boss->id,
        'boss_name' => $first->boss->nombre_completo,
        'year' => $first->year,
        'month' => $first->month,
        'assigned_workers' => $items->map(function ($item) {
          return [
            'id' => $item->worker->id,
            'name' => $item->worker->nombre_completo,
          ];
        })->values(),
      ];
    })->values();

    return response()->json(['data' => $grouped]);
  }

  public function show($id)
  {
    $boss = Worker::with('advisorsBoss')->find($id);
    if (!$boss) {
      throw new Exception('Jefe no encontrado');
    }
    return new ApAssignmentLeadershipResource($boss);
  }

  public function update(mixed $data)
  {
    $boss = Worker::findOrFail($data['boss_id']);
    $boss->advisorsBoss()->sync($data['assigned_workers']);

    $boss = Worker::findOrFail($data['boss_id']);
    if ($boss->sede->empresa_id !== Constants::COMPANY_AP) {
      throw new Exception('El jefe seleccionado no pertenece a la empresa AP');
    }

    if (isset($data['year']) && isset($data['month'])) {
      $existing = ApAssignmentLeadershipPeriod::withTrashed()
        ->where('boss_id', $data['boss_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->get()
        ->keyBy('worker_id');

      $newAsesores = collect($data['assigned_workers'])->mapWithKeys(fn($id) => [$id => $id]);

      foreach ($newAsesores as $asesorId) {
        if ($existing->has($asesorId)) {
          $record = $existing[$asesorId];
          if ($record->trashed()) {
            $record->restore();
          }
        } else {
          ApAssignmentLeadershipPeriod::create([
            'boss_id' => $data['boss_id'],
            'worker_id' => $asesorId,
            'year' => $data['year'],
            'month' => $data['month'],
          ]);
        }
      }

      $toDelete = $existing->keys()->diff($newAsesores->keys());
      if ($toDelete->isNotEmpty()) {
        ApAssignmentLeadershipPeriod::where('boss_id', $data['boss_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->whereIn('worker_id', $toDelete)
          ->delete();
      }
    }

    return new ApAssignmentLeadershipResource($boss->load('advisorsBoss'));
  }
}
