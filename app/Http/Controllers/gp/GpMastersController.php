<?php

namespace App\Http\Controllers\gp;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\IndexGpMastersRequest;
use App\Http\Requests\gp\StoreGpMastersRequest;
use App\Http\Requests\gp\UpdateGpMastersRequest;
use App\Http\Services\gp\GpMastersService;

class GpMastersController extends Controller
{
  protected GpMastersService $service;

  public function __construct(GpMastersService $service)
  {
    $this->service = $service;
  }

  public function index(IndexGpMastersRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreGpMastersRequest $request)
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

  public function update(UpdateGpMastersRequest $request, $id)
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

  /**
   * Obtener todos los tipos registrados en Master General (cacheado)
   * GET /api/gp/gpMasters/types
   */
  public function getTypes()
  {
    try {
      return $this->service->getTypes();
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}