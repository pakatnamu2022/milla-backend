<?php

namespace App\Http\Controllers\ap\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\maestroGeneral\IndexUnitMeasurementRequest;
use App\Http\Requests\ap\maestroGeneral\StoreUnitMeasurementRequest;
use App\Http\Requests\ap\maestroGeneral\UpdateUnitMeasurementRequest;
use App\Http\Services\ap\maestroGeneral\UnitMeasurementService;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use Illuminate\Http\Request;

class UnitMeasurementController extends Controller
{
  protected UnitMeasurementService $service;

  public function __construct(UnitMeasurementService $service)
  {
    $this->service = $service;
  }

  public function index(IndexUnitMeasurementRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreUnitMeasurementRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateUnitMeasurementRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
