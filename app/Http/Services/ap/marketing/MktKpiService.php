<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktKpiResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\marketing\MktKpi;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktKpiService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktKpi::query()->with(['activity:id,name', 'currency:id,name,code,symbol']),
      $request,
      MktKpi::filters,
      MktKpi::sorts,
      MktKpiResource::class
    );
  }

  public function find(int $id): MktKpi
  {
    $kpi = MktKpi::with(['activity', 'currency'])->find($id);
    if (!$kpi) {
      throw new Exception('KPI no encontrado');
    }
    return $kpi;
  }

  public function store(mixed $data): MktKpiResource
  {
    DB::beginTransaction();
    try {
      $kpi = MktKpi::create($data);
      $kpi->load(['activity', 'currency']);
      DB::commit();
      return new MktKpiResource($kpi);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktKpiResource
  {
    return new MktKpiResource($this->find($id));
  }

  public function update(mixed $data): MktKpiResource
  {
    $kpi = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $kpi->update($data);
      $kpi->load(['activity', 'currency']);
      DB::commit();
      return new MktKpiResource($kpi);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $kpi = $this->find($id);
      $kpi->delete();
      DB::commit();
      return ['message' => 'KPI eliminado correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
