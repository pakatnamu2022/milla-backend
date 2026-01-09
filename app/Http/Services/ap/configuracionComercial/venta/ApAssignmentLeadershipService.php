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

  /**
   * List all assignment records (individual assignments)
   */
  public function list(Request $request)
  {
    $query = ApAssignmentLeadership::with(['boss', 'worker']);

    return $this->getFilteredResults(
      $query,
      $request,
      ApAssignmentLeadership::filters,
      ApAssignmentLeadership::sorts,
      ApAssignmentLeadershipResource::class,
    );
  }

  /**
   * Get assignments grouped by boss (for management view)
   */
  public function getGroupedByBoss(Request $request)
  {
    // Build query with relationships and apply filters
    $query = ApAssignmentLeadership::with(['boss', 'worker']);
    $query = $this->applyFilters($query, $request, ApAssignmentLeadership::filters);
    $query = $this->applySorting($query, $request, ApAssignmentLeadership::sorts);

    // Get all records and group them
    $allRecords = $query->get();
    $grouped = $this->groupAssignments($allRecords);

    // Paginate the grouped collection
    return $this->paginateCollection($grouped, $request);
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

  public function show($id, Request $request)
  {
    $year = $request->query('year') ?? now()->year;
    $month = $request->query('month') ?? now()->month;

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
        ->where('status', 1)
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
          ->where('status', 1)
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
          ->where('status', 1)
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
