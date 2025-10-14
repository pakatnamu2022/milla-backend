<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignCompanyBranchResource;
use App\Http\Services\BaseService;
use App\Http\Utils\Constants;
use App\Models\ap\configuracionComercial\venta\ApAssignCompanyBranch;
use App\Models\gp\maestroGeneral\Sede;
use Exception;
use Illuminate\Http\Request;

class ApAssignCompanyBranchService extends BaseService
{
  public function list(Request $request)
  {
    $year = $request->query('year');
    $month = $request->query('month');
    $sede = $request->query('search');
    $all = filter_var($request->query('all', false), FILTER_VALIDATE_BOOLEAN);
    $perPage = (int)$request->query('per_page', 10);
    $page = (int)$request->query('page', 1);

    $query = ApAssignCompanyBranch::with(['sede', 'worker'])
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($sede, function ($q) use ($sede) {
        $q->whereHas('sede', function ($sub) use ($sede) {
          $sub->where('abreviatura', 'like', "%{$sede}%");
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
    return $records->groupBy('sede_id')->map(function ($items) {
      $first = $items->first();
      return [
        'sede_id' => $first->sede->id,
        'sede' => $first->sede->abreviatura,
        'year' => $first->year,
        'month' => $first->month,
        'period' => "{$first->year}-{$first->month}",
        'assigned_workers' => $items->map(function ($item) {
          return [
            'id' => $item->worker->id,
            'name' => $item->worker->nombre_completo,
          ];
        })->values(),
        'workers_count' => $items->count(),
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

    $query = ApAssignCompanyBranch::where('sede_id', $id)
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month));

    $items = $query->get();

    if ($items->isEmpty()) {
      return response()->json(['message' => 'Sede no encontrado'], 404);
    }

    return new ApAssignCompanyBranchResource($items);
  }

  public function store(array $data)
  {
    $sede = Sede::findOrFail($data['sede_id']);
    if ($sede->empresa_id !== Constants::COMPANY_AP) {
      throw new Exception('La sede no pertenece a la empresa AP');
    }

    $existing = ApAssignCompanyBranch::where('sede_id', $data['sede_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->where(function ($q) {
        $q->where('status', 1)->orWhereNull('deleted_at');
      })
      ->first();
    if ($existing) {
      throw new Exception('Ya existe una asignación para esta sede en el periodo seleccionado.');
    }

    foreach ($data['assigned_workers'] as $asesorId) {
      ApAssignCompanyBranch::create([
        'sede_id' => $data['sede_id'],
        'worker_id' => $asesorId,
        'year' => $data['year'],
        'month' => $data['month'],
        'status' => 1,
      ]);
    }

    $items = ApAssignCompanyBranch::where('sede_id', $data['sede_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->get();

    return new ApAssignCompanyBranchResource($items);
  }

  public function update(mixed $data)
  {
    $sede = Sede::findOrFail($data['sede_id']);
    $status = $data['status'] ?? 1;
    if ($sede->empresa_id !== Constants::COMPANY_AP) {
      throw new Exception('El sede seleccionado no pertenece a la empresa AP');
    }

    $existingPeriod = ApAssignCompanyBranch::where('sede_id', $data['sede_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->exists();
    if (!$existingPeriod) {
      throw new Exception('No existe una asignación para esta sede en el periodo seleccionado.');
    }

    if (isset($data['status'])) {
      ApAssignCompanyBranch::where('sede_id', $data['sede_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->update(['status' => $status]);
    } else {
      $allInactive = ApAssignCompanyBranch::where('sede_id', $data['sede_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->where('status', 0)
        ->count();
      if ($allInactive) {
        throw new Exception('No se puede editar la asignación porque todos los asesores están inactivos.');
      }
    }

    if (isset($data['year']) && isset($data['month']) && isset($data['assigned_workers'])) {
      $existing = ApAssignCompanyBranch::withTrashed()
        ->where('sede_id', $data['sede_id'])
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
          ApAssignCompanyBranch::create([
            'sede_id' => $data['sede_id'],
            'worker_id' => $asesorId,
            'year' => $data['year'],
            'month' => $data['month'],
            'status' => 1,
          ]);
        }
      }

      $toDelete = $existing->keys()->diff($newAsesores->keys());
      if ($toDelete->isNotEmpty()) {
        ApAssignCompanyBranch::where('sede_id', $data['sede_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->whereIn('worker_id', $toDelete)
          ->delete();
      }
    }

    $query = ApAssignCompanyBranch::where('sede_id', $data['sede_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month']);

    $items = $query->get();

    if ($items->isEmpty()) {
      throw new Exception("Sede no encontrado.");
    }

    return new ApAssignCompanyBranchResource($items);
  }

  public function getWorkersBySede($sedeId)
  {
    $workers = ApAssignCompanyBranch::where('sede_id', $sedeId)
      ->where('status', 1)
      ->with('worker:id,nombre_completo')
      ->get()
      ->map(function ($assignment) {
        return [
          'id' => $assignment->worker->id,
          'name' => $assignment->worker->nombre_completo
        ];
      })
      ->unique('id')
      ->values();

    return $workers;
  }
}
