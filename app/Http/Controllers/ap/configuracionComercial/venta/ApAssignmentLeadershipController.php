<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApAssignmentLeadershipRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApAssignmentLeadershipRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApAssignmentLeadershipRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApAssignmentLeadershipService;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use Illuminate\Http\Request;

class ApAssignmentLeadershipController extends Controller
{
  protected ApAssignmentLeadershipService $service;

  public function __construct(ApAssignmentLeadershipService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApAssignmentLeadershipRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function indexRecord(IndexApAssignmentLeadershipRequest $request)
  {
    try {
      return $this->service->listRecord($request);
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

  public function update(UpdateApAssignmentLeadershipRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['boss_id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
