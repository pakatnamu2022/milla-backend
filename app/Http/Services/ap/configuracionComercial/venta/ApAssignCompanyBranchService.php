<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignCompanyBranchResource;
use App\Http\Services\BaseService;
use App\Http\Utils\Constants;
use App\Models\ap\configuracionComercial\venta\ApAssignCompanyBranchPeriod;
use App\Models\gp\gestionsistema\CompanyBranch;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Http\Request;
use Exception;

class ApAssignCompanyBranchService extends BaseService
{
  public function list(Request $request)
  {
//    return $this->getFilteredResults(
//      CompanyBranch::with('workers')
//        ->whereNotNull('company_id')
//        ->where('company_id', Constants::COMPANY_AP),
//      $request,
//      CompanyBranch::filters,
//      CompanyBranch::sorts,
//      ApAssignCompanyBranchResource::class
//    );
    return $this->getFilteredResults(
      Sede::with('workers')
        ->whereNotNull('empresa_id')
        ->where('empresa_id', Constants::COMPANY_AP),
      $request,
      Sede::filters,
      Sede::sorts,
      ApAssignCompanyBranchResource::class
    );
  }

  public function listRecord(Request $request)
  {
    $year = $request->query('year');
    $month = $request->query('month');
    $sede = $request->query('sede');

    $query = ApAssignCompanyBranchPeriod::with(['sede', 'worker'])
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($sede, function ($q) use ($sede) {
        $q->whereHas('sede', function ($sub) use ($sede) {
          $sub->where('abreviatura', 'like', "%{$sede}%");
        });
      });

    $periodos = $query->get();

    $grouped = $periodos->groupBy('sede_id')->map(function ($items) {
      $first = $items->first();

      return [
        'sede_id' => $first->sede->id,
        'sede' => $first->sede->abreviatura,
        'year' => $first->year,
        'month' => $first->month,
        'workers' => $items->map(function ($item) {
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
    $sede = Sede::with('workers')->find($id);
    if (!$sede) {
      throw new Exception('Sede no encontrada');
    }
    return new ApAssignCompanyBranchResource($sede);
  }

  public function store(array $data)
  {
    $sede = Sede::findOrFail($data['sede_id']);
    $sede->workers()->sync($data['workers']);
    return new ApAssignCompanyBranchResource($sede->load('workers'));
  }

  public function update(mixed $data)
  {
    $sede = Sede::findOrFail($data['sede_id']);
    $sede->workers()->sync($data['workers']);

    if (isset($data['year']) && isset($data['month'])) {
      $existing = ApAssignCompanyBranchPeriod::withTrashed()
        ->where('sede_id', $data['sede_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->get()
        ->keyBy('worker_id');

      $newAsesores = collect($data['workers'])->mapWithKeys(fn($id) => [$id => $id]);

      foreach ($newAsesores as $asesorId) {
        if ($existing->has($asesorId)) {
          $record = $existing[$asesorId];
          if ($record->trashed()) {
            $record->restore();
          }
        } else {
          ApAssignCompanyBranchPeriod::create([
            'sede_id' => $data['sede_id'],
            'worker_id' => $asesorId,
            'year' => $data['year'],
            'month' => $data['month'],
          ]);
        }
      }

      $toDelete = $existing->keys()->diff($newAsesores->keys());
      if ($toDelete->isNotEmpty()) {
        ApAssignCompanyBranchPeriod::where('sede_id', $data['sede_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->whereIn('worker_id', $toDelete)
          ->delete();
      }
    }

    return new ApAssignCompanyBranchResource($sede->load('workers'));
  }
}
