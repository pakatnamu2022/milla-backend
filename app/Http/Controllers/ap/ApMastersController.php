<?php

namespace App\Http\Controllers\ap;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\IndexApMastersRequest;
use App\Http\Requests\ap\StoreApMastersRequest;
use App\Http\Requests\ap\UpdateApMastersRequest;
use App\Http\Services\ap\ApMastersService;

class ApMastersController extends Controller
{
  protected ApMastersService $service;

  public function __construct(ApMastersService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApMastersRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApMastersRequest $request)
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

  public function update(UpdateApMastersRequest $request, $id)
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
   * Obtener todos los tipos registrados en Master Comercial (cacheado)
   * GET /api/ap/commercialMasters/types
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
