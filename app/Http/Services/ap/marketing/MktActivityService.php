<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktActivityLocationResource;
use App\Http\Resources\ap\marketing\MktActivityResource;
use App\Http\Resources\ap\marketing\MktSupportResource;
use App\Http\Services\BaseService;
use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktActivityLocation;
use App\Models\ap\marketing\MktSupport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktActivityService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktActivity::class,
      $request,
      MktActivity::filters,
      MktActivity::sorts,
      MktActivityResource::class
    );
  }

  private function find(int $id): MktActivity
  {
    $activity = MktActivity::with(['budget', 'currency', 'supplier', 'locations', 'proposals', 'kpis'])->find($id);
    if (!$activity) {
      throw new Exception('Actividad no encontrada');
    }
    return $activity;
  }

  public function store(mixed $data): MktActivityResource
  {
    DB::beginTransaction();
    try {
      $activity = MktActivity::create($data);
      $activity->load(['budget', 'currency', 'supplier', 'locations']);
      DB::commit();
      return new MktActivityResource($activity);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktActivityResource
  {
    return new MktActivityResource($this->find($id));
  }

  public function update(mixed $data): MktActivityResource
  {
    $activity = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $activity->update($data);
      $activity->load(['budget', 'currency', 'supplier', 'locations']);
      DB::commit();
      return new MktActivityResource($activity);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id)
  {
    $activity = $this->find($id);
    $activity->delete();
    return response()->json(['message' => 'Actividad eliminada correctamente']);
  }

  public function addLocation(int $activityId, mixed $data): MktActivityLocationResource
  {
    $activity = $this->find($activityId);
    DB::beginTransaction();
    try {
      $data['activity_id'] = $activity->id;
      $location = MktActivityLocation::create($data);
      $location->load(['sede', 'currency']);
      DB::commit();
      return new MktActivityLocationResource($location);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function addSupport(int $activityId, mixed $data): MktSupportResource
  {
    $activity = $this->find($activityId);
    DB::beginTransaction();
    try {
      $data['activity_id'] = $activity->id;
      $support = MktSupport::create($data);
      $support->load(['supplier', 'currency', 'purchaseOrder']);
      DB::commit();
      return new MktSupportResource($support);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function changeStatus(int $id, string $status): MktActivityResource
  {
    $validStatuses = [
      MktActivity::STATUS_PLANNED,
      MktActivity::STATUS_IN_PROGRESS,
      MktActivity::STATUS_EXECUTED,
      MktActivity::STATUS_CANCELLED,
    ];

    if (!in_array($status, $validStatuses)) {
      throw new Exception('Estado no válido');
    }

    $activity = $this->find($id);
    DB::beginTransaction();
    try {
      $activity->update(['status' => $status]);
      DB::commit();
      return new MktActivityResource($activity);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
