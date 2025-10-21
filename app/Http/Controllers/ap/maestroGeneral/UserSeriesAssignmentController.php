<?php

namespace App\Http\Controllers\ap\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\maestroGeneral\IndexUserSeriesAssignmentRequest;
use App\Http\Requests\ap\maestroGeneral\StoreUserSeriesAssignmentRequest;
use App\Http\Requests\ap\maestroGeneral\UpdateUserSeriesAssignmentRequest;
use App\Http\Services\ap\maestroGeneral\UserSeriesAssignmentService;

class UserSeriesAssignmentController extends Controller
{
  protected UserSeriesAssignmentService $service;

  public function __construct(UserSeriesAssignmentService $service)
  {
    $this->service = $service;
  }

  public function index(IndexUserSeriesAssignmentRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreUserSeriesAssignmentRequest $request)
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

  public function update(UpdateUserSeriesAssignmentRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['worker_id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getAuthorizedSeries(IndexUserSeriesAssignmentRequest $request)
  {
    try {
      return $this->service->getAuthorizedSeries($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
