<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApAssignmentLeadershipRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApAssignmentLeadershipRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApAssignmentLeadershipRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApAssignmentLeadershipService;

class ApAssignmentLeadershipController extends Controller
{
  protected ApAssignmentLeadershipService $service;

  public function __construct(ApAssignmentLeadershipService $service)
  {
    $this->service = $service;
  }

  /**
   * Get individual assignment records
   */
  public function index(IndexApAssignmentLeadershipRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get assignments grouped by boss (for management view)
   */
  public function grouped(IndexApAssignmentLeadershipRequest $request)
  {
    try {
      return $this->service->getGroupedByBoss($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id, IndexApAssignmentLeadershipRequest $request)
  {
    try {
      return $this->service->show($id, $request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApAssignmentLeadershipRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateApAssignmentLeadershipRequest $request, $id)
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
