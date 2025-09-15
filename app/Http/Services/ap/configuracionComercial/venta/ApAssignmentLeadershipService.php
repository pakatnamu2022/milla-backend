<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignmentLeadershipResource;
use App\Http\Services\BaseService;
use App\Http\Utils\Constants;
use Illuminate\Http\Request;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;

class ApAssignmentLeadershipService extends BaseService
{

  public function list(Request $request)
  {
    $year = $request->query('year');
    $month = $request->query('month');
    $boss = $request->query('search');
    $all = filter_var($request->query('all', false), FILTER_VALIDATE_BOOLEAN);
    $perPage = (int)$request->query('per_page', 10);
    $page = (int)$request->query('page', 1);

    $query = ApAssignmentLeadership::with(['boss', 'worker'])
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($boss, function ($q) use ($boss) {
        $q->whereHas('boss', function ($sub) use ($boss) {
          $sub->where('nombre_completo', 'like', "%{$boss}%");
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
    return $records->groupBy('boss_id')->map(function ($items) {
      $first = $items->first();
      return [
        'boss_id' => $first->boss->id,
        'boss_position' => $first->boss->position->name,
        'boss_name' => $first->boss->nombre_completo,
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
    $year = $request->query('year');
    $month = $request->query('month');

    $query = ApAssignmentLeadership::where('boss_id', $id)
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month));

    $items = $query->get();

    if ($items->isEmpty()) {
      return response()->json(['message' => 'Jefe no encontrado'], 404);
    }

    return new ApAssignmentLeadershipResource($items);
  }

  public function store(array $data)
  {
    $boss = Worker::findOrFail($data['boss_id']);
    if ($boss->sede->empresa_id !== Constants::COMPANY_AP) {
      throw new Exception('El jefe seleccionado no pertenece a la empresa AP');
    }

    $existing = ApAssignmentLeadership::where('boss_id', $data['boss_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->where(function ($q) {
        $q->where('status', 1)->orWhereNull('deleted_at');
      })
      ->first();
    if ($existing) {
      throw new Exception('Ya existe una asignación para este jefe en el periodo seleccionado.');
    }

    foreach ($data['assigned_workers'] as $asesorId) {
      $existsElsewhere = ApAssignmentLeadership::where('worker_id', $asesorId)
        ->where('boss_id', '!=', $data['boss_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->where(function ($q) {
          $q->where('status', 1)->orWhereNull('deleted_at');
        })
        ->exists();

      $nameAsesor = Worker::find($asesorId)->nombre_completo;

      if ($existsElsewhere) {
        throw new Exception("El asesor {$nameAsesor} ya está asignado a otro jefe en este periodo.");
      }

      ApAssignmentLeadership::create([
        'boss_id' => $data['boss_id'],
        'worker_id' => $asesorId,
        'year' => $data['year'],
        'month' => $data['month'],
        'status' => 1,
      ]);
    }

    $items = ApAssignmentLeadership::with(['boss.position', 'worker'])
      ->where('boss_id', $data['boss_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->get();

    return new ApAssignmentLeadershipResource($items);
  }

  public function update(mixed $data)
  {
    $boss = Worker::findOrFail($data['boss_id']);
    $status = $data['status'] ?? 1;
    if ($boss->sede->empresa_id !== Constants::COMPANY_AP) {
      throw new Exception('El jefe seleccionado no pertenece a la empresa AP');
    }

    $existingPeriod = ApAssignmentLeadership::where('boss_id', $data['boss_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month'])
      ->exists();
    if (!$existingPeriod) {
      throw new Exception('No existe una asignación para este jefe en el periodo seleccionado.');
    }

    if (!isset($data['assigned_workers'])) {
      $assignedWorkers = ApAssignmentLeadership::where('boss_id', $data['boss_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->pluck('worker_id')
        ->toArray();

      foreach ($assignedWorkers as $asesorId) {
        $existsElsewhere = ApAssignmentLeadership::where('worker_id', $asesorId)
          ->where('boss_id', '!=', $data['boss_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->where(function ($q) {
            $q->where('status', 1);
          })
          ->exists();

        $nameAsesor = Worker::find($asesorId)->nombre_completo;

        if ($existsElsewhere) {
          throw new Exception("El asesor {$nameAsesor} ya está asignado a otro jefe en este periodo.");
        }
      }
    }

    if (isset($data['status'])) {
      ApAssignmentLeadership::where('boss_id', $data['boss_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->update(['status' => $status]);
    } else {
      $allInactive = ApAssignmentLeadership::where('boss_id', $data['boss_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->where('status', 0)
        ->count();
      if ($allInactive) {
        throw new Exception('No se puede editar la asignación porque todos sus asesores están inactivos.');
      }
    }

    if (isset($data['year']) && isset($data['month']) && isset($data['assigned_workers'])) {
      $existing = ApAssignmentLeadership::withTrashed()
        ->where('boss_id', $data['boss_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->get()
        ->keyBy('worker_id');

      $newAsesores = collect($data['assigned_workers'])->mapWithKeys(fn($id) => [$id => $id]);

      foreach ($newAsesores as $asesorId) {
        $nameAsesor = Worker::find($asesorId)->nombre_completo;

        $existsElsewhere = ApAssignmentLeadership::where('worker_id', $asesorId)
          ->where('boss_id', '!=', $data['boss_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->where(function ($q) {
            $q->where('status', 1);
          })
          ->exists();

        if ($existsElsewhere) {
          throw new Exception("Asesor {$nameAsesor} ya está asignado a otro jefe en este periodo." . $existsElsewhere);
        }

        if ($existing->has($asesorId)) {
          $record = $existing[$asesorId];
          if ($record->trashed()) {
            $record->restore();
          }
        } else {
          ApAssignmentLeadership::create([
            'boss_id' => $data['boss_id'],
            'worker_id' => $asesorId,
            'year' => $data['year'],
            'month' => $data['month'],
            'status' => 1,
          ]);
        }
      }

      $toDelete = $existing->keys()->diff($newAsesores->keys());
      if ($toDelete->isNotEmpty()) {
        ApAssignmentLeadership::where('boss_id', $data['boss_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->whereIn('worker_id', $toDelete)
          ->delete();
      }
    }

    $query = ApAssignmentLeadership::where('boss_id', $data['boss_id'])
      ->where('year', $data['year'])
      ->where('month', $data['month']);

    $items = $query->get();

    if ($items->isEmpty()) {
      throw new Exception("Jefe de ventas no encontrado.");
    }

    return new ApAssignmentLeadershipResource($items);
  }
}
