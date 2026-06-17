<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\WorkScheduleResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\WorkSchedule;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkScheduleService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      WorkSchedule::with('details'),
      $request,
      WorkSchedule::filters,
      WorkSchedule::sorts,
      WorkScheduleResource::class,
    );
  }

  public function show(int $id): WorkScheduleResource
  {
    $schedule = WorkSchedule::with('details')->findOrFail($id);
    return new WorkScheduleResource($schedule);
  }

  public function store(array $data): WorkScheduleResource
  {
    DB::beginTransaction();
    try {
      $details = $data['details'] ?? [];
      unset($data['details']);

      $schedule = WorkSchedule::create($data);
      $this->syncDetails($schedule, $details);

      DB::commit();
      $schedule->load('details');
      return new WorkScheduleResource($schedule);
    } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function update(array $data, int $id): WorkScheduleResource
  {
    DB::beginTransaction();
    try {
      $schedule = WorkSchedule::findOrFail($id);
      $details  = $data['details'] ?? null;
      unset($data['details']);

      $schedule->update($data);

      if ($details !== null) {
        $this->syncDetails($schedule, $details);
      }

      DB::commit();
      $schedule->load('details');
      return new WorkScheduleResource($schedule);
    } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    $schedule = WorkSchedule::findOrFail($id);

    if ($schedule->workers()->exists()) {
      throw new Exception('No se puede eliminar: hay trabajadores asignados a este horario.');
    }

    $schedule->details()->delete();
    $schedule->delete();

    return ['message' => 'Horario eliminado correctamente.'];
  }

  /**
   * Bulk-assign a work schedule to workers filtered by cargo_id, area_id and/or sede_id.
   * At least one filter is required.
   *
   * @param array{work_schedule_id:int, cargo_id?:int, area_id?:int, sede_id?:int, empresa_id?:int} $data
   */
  public function assignBulk(array $data): array
  {
    $scheduleId = $data['work_schedule_id'];

    WorkSchedule::findOrFail($scheduleId);

    $query = Worker::query()->working();

    $filtered = false;

    if (!empty($data['cargo_id'])) {
      $query->where('cargo_id', $data['cargo_id']);
      $filtered = true;
    }

    if (!empty($data['area_id'])) {
      $query->where('area_id', $data['area_id']);
      $filtered = true;
    }

    if (!empty($data['sede_id'])) {
      $query->where('sede_id', $data['sede_id']);
      $filtered = true;
    }

    if (!empty($data['empresa_id'])) {
      $query->whereHas('sede', fn($q) => $q->where('empresa_id', $data['empresa_id']));
      $filtered = true;
    }

    if (!$filtered) {
      throw new Exception('Debes especificar al menos un filtro: cargo_id, area_id, sede_id o empresa_id.');
    }

    $count = $query->update(['work_schedule_id' => $scheduleId]);

    return [
      'message'  => "Horario asignado correctamente a {$count} trabajador(es).",
      'affected' => $count,
    ];
  }

  private function syncDetails(WorkSchedule $schedule, array $details): void
  {
    $schedule->details()->delete();

    if (empty($details)) {
      return;
    }

    $rows = array_map(fn($d) => [
      'work_schedule_id' => $schedule->id,
      'day_of_week'      => $d['day_of_week'],
      'checkin'          => $d['checkin'],
      'lunch_out'        => $d['lunch_out'] ?? null,
      'lunch_in'         => $d['lunch_in'] ?? null,
      'checkout'         => $d['checkout'],
    ], $details);

    $schedule->details()->insert($rows);
  }
}
