<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemRateRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemRateRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemRateRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRateResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemRateService;
use Throwable;

class PerDiemRateController extends Controller
{
  protected PerDiemRateService $service;

  public function __construct(PerDiemRateService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of per diem rates
   */
  public function index(IndexPerDiemRateRequest $request)
  {
    try {
      return $this->service->index($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created per diem rate
   */
  public function store(StorePerDiemRateRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified per diem rate
   */
  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified per diem rate
   */
  public function update(UpdatePerDiemRateRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified per diem rate
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
