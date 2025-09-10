<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApCommercialManagerBrandGroupResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApCommercialMasters;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroup;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroupPeriod;
use Illuminate\Http\Request;
use Exception;

class ApCommercialManagerBrandGroupService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApCommercialMasters::ofType('GRUPO_MARCAS'),
      $request,
      ApCommercialMasters::filters,
      ApCommercialMasters::sorts,
      ApCommercialManagerBrandGroupResource::class
    );
  }

  public function listRecord(Request $request)
  {
    $year = $request->query('year');
    $month = $request->query('month');
    $brandGroup = $request->query('brand_group');

    $query = ApCommercialManagerBrandGroupPeriod::with(['brandGroup', 'commercialManager'])
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($brandGroup, function ($q) use ($brandGroup) {
        $q->whereHas('brandGroup', function ($sub) use ($brandGroup) {
          $sub->where('description', 'like', "%{$brandGroup}%");
        });
      });

    $periodos = $query->get();

    $grouped = $periodos->groupBy('brand_group_id')->map(function ($items) {
      $first = $items->first();

      return [
        'brand_group_id' => $first->brandGroup->id,
        'brand_group' => $first->brandGroup->description,
        'year' => $first->year,
        'month' => $first->month,
        'commercial_managers' => $items->map(function ($item) {
          return [
            'id' => $item->commercialManager->id,
            'name' => $item->commercialManager->nombre_completo,
          ];
        })->values(),
      ];
    })->values();

    return response()->json(['data' => $grouped]);
  }

  public function show($id)
  {
    $brandGroup = ApCommercialMasters::ofType('GRUPO_MARCAS')->find($id);
    if (!$brandGroup) {
      throw new Exception('Grupo de marca no encontrada');
    }
    return new ApCommercialManagerBrandGroupResource($brandGroup);
  }

  public function update(mixed $data)
  {
    $brandGroup = ApCommercialMasters::findOrFail($data['brand_group_id']);
    $brandGroup->commercialManagers()->sync($data['commercial_managers']);

    if (isset($data['year']) && isset($data['month'])) {
      $existing = ApCommercialManagerBrandGroupPeriod::withTrashed()
        ->where('brand_group_id', $data['brand_group_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->get()
        ->keyBy('commercial_manager_id');

      $newCommercialManagers = collect($data['commercial_managers'])->mapWithKeys(fn($id) => [$id => $id]);

      foreach ($newCommercialManagers as $commercialManager) {
        if ($existing->has($commercialManager)) {
          $record = $existing[$commercialManager];
          if ($record->trashed()) {
            $record->restore();
          }
        } else {
          ApCommercialManagerBrandGroupPeriod::create([
            'brand_group_id' => $data['brand_group_id'],
            'commercial_manager_id' => $commercialManager,
            'year' => $data['year'],
            'month' => $data['month'],
          ]);
        }
      }

      $toDelete = $existing->keys()->diff($newCommercialManagers->keys());
      if ($toDelete->isNotEmpty()) {
        ApCommercialManagerBrandGroupPeriod::where('brand_group_id', $data['brand_group_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->whereIn('commercial_manager_id', $toDelete)
          ->delete();
      }
    }

    return new ApCommercialManagerBrandGroupResource($brandGroup->load('commercialManagers'));
  }
}
