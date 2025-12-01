<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApCommercialManagerBrandGroupResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroup;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroupPeriod;
use Exception;
use Illuminate\Http\Request;

class ApCommercialManagerBrandGroupService extends BaseService
{
  public function list(Request $request)
  {
    $year = $request->query('year');
    $month = $request->query('month');
    $brandGroup = $request->query('search');
    $all = filter_var($request->query('all', false), FILTER_VALIDATE_BOOLEAN);
    $perPage = (int)$request->query('per_page', 10);
    $page = (int)$request->query('page', 1);

    $query = ApCommercialManagerBrandGroup::with(['brandGroup', 'commercialManager'])
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($brandGroup, function ($q) use ($brandGroup) {
        $q->whereHas('brandGroup', function ($sub) use ($brandGroup) {
          $sub->where('description', 'like', "%{$brandGroup}%");
        });
      });

    if ($all) {
      $allRecords = $query->get();
      $grouped = $this->groupAssignments($allRecords);

      return response()->json($grouped);
    }

    $allRecords = $query->get();
    $allGrouped = $this->groupAssignments($allRecords);

    $total = $allGrouped->count();
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;

    $paginatedGroups = $allGrouped->slice($offset, $perPage)->values();

    $baseUrl = $request->url();
    $queryParams = $request->except('page');

    return response()->json([
      'data' => $paginatedGroups,
      'links' => [
        'first' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => 1])),
        'last' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $totalPages])),
        'prev' => $page > 1 ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $page - 1])) : null,
        'next' => $page < $totalPages ? $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $page + 1])) : null,
      ],
      'meta' => [
        'current_page' => $page,
        'from' => $total > 0 ? $offset + 1 : null,
        'last_page' => $totalPages,
        'path' => $baseUrl,
        'per_page' => $perPage,
        'to' => min($offset + $perPage, $total),
        'total' => $total,
      ]
    ]);
  }

  private function groupAssignments($records)
  {
    return $records->groupBy('brand_group_id')->map(function ($items) {
      $first = $items->first();
      return [
        'brand_group_id' => $first->brandGroup->id,
        'brand_group' => $first->brandGroup->description,
        'year' => $first->year,
        'month' => $first->month,
        'period' => "{$first->year}-{$first->month}",
        'commercial_managers' => $items->map(function ($item) {
          return [
            'id' => $item->commercialManager->id,
            'name' => $item->commercialManager->nombre_completo,
          ];
        })->values(),
        'commercial_managers_count' => $items->count(),
        'status' => $first->status
      ];
    })->values();
  }

  private function buildUrl($baseUrl, $params)
  {
    return $baseUrl . '?' . http_build_query($params);
  }

  public function show($id, Request $request)
  {
    $year = $request->query('year') ?? now()->year;
    $month = $request->query('month') ?? now()->month;

    $query = ApCommercialManagerBrandGroup::where('brand_group_id', $id)
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month));

    $items = $query->get();

    if ($items->isEmpty()) {
      return response()->json(['message' => 'Grupo de marca no encontrado'], 404);
    }

    return new ApCommercialManagerBrandGroupResource($items);
  }

  public function store(array $data)
  {
    $existing = ApCommercialManagerBrandGroup::where('brand_group_id', $data['brand_group_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->where(function ($q) {
        $q->where('status', 1)->orWhereNull('deleted_at');
      })
      ->first();
    if ($existing) {
      throw new Exception('Ya existe una asignación para este grupo de marca en el periodo seleccionado.');
    }

    foreach ($data['commercial_managers'] as $asesorId) {
      ApCommercialManagerBrandGroup::create([
        'brand_group_id' => $data['brand_group_id'],
        'commercial_manager_id' => $asesorId,
        'year' => $data['year'],
        'month' => $data['month'],
        'status' => 1,
      ]);
    }

    $items = ApCommercialManagerBrandGroup::where('brand_group_id', $data['brand_group_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->get();

    return new ApCommercialManagerBrandGroupResource($items);
  }

  public function update(mixed $data)
  {
    $status = $data['status'] ?? 1;
    $existingPeriod = ApCommercialManagerBrandGroup::where('brand_group_id', $data['brand_group_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->exists();
    if (!$existingPeriod) {
      throw new Exception('No existe una asignación para este grupo de marca en el periodo seleccionado.');
    }

    if (isset($data['status'])) {
      ApCommercialManagerBrandGroup::where('brand_group_id', $data['brand_group_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->update(['status' => $status]);
    } else {
      $allInactive = ApCommercialManagerBrandGroup::where('brand_group_id', $data['brand_group_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->where('status', 0)
        ->count();
      if ($allInactive) {
        throw new Exception('No se puede editar la asignación porque todos los gerentes comerciales están inactivos.');
      }
    }

    if (isset($data['year']) && isset($data['month']) && isset($data['commercial_managers'])) {
      $existing = ApCommercialManagerBrandGroup::withTrashed()
        ->where('brand_group_id', $data['brand_group_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->get()
        ->keyBy('commercial_manager_id');

      $newAsesores = collect($data['commercial_managers'])->mapWithKeys(fn($id) => [$id => $id]);

      foreach ($newAsesores as $asesorId) {
        if ($existing->has($asesorId)) {
          $record = $existing[$asesorId];
          if ($record->trashed()) {
            $record->restore();
          }
        } else {
          ApCommercialManagerBrandGroup::create([
            'brand_group_id' => $data['brand_group_id'],
            'commercial_manager_id' => $asesorId,
            'year' => $data['year'],
            'month' => $data['month'],
            'status' => 1,
          ]);
        }
      }

      $toDelete = $existing->keys()->diff($newAsesores->keys());
      if ($toDelete->isNotEmpty()) {
        ApCommercialManagerBrandGroup::where('brand_group_id', $data['brand_group_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->whereIn('commercial_manager_id', $toDelete)
          ->delete();
      }
    }

    $query = ApCommercialManagerBrandGroup::where('brand_group_id', $data['brand_group_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month']);

    $items = $query->get();

    return new ApCommercialManagerBrandGroupResource($items);
  }
}
