<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\IndexTelephonePlanRequest;
use App\Http\Requests\gp\tics\StoreTelephonePlanRequest;
use App\Http\Requests\gp\tics\UpdateTelephonePlanRequest;
use App\Http\Services\gp\tics\TelephonePlanService;

class TelephonePlanController extends Controller
{
  protected TelephonePlanService $service;

  public function __construct(TelephonePlanService $service)
  {
    $this->service = $service;
  }

  /**
   * @param IndexTelephonePlanRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function index(IndexTelephonePlanRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Exception $exception) {
      return $this->error($exception->getMessage());
    }
  }

  /**
   * @param StoreTelephonePlanRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(StoreTelephonePlanRequest $request)
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->store($data));
    } catch (\Exception $exception) {
      return $this->error($exception->getMessage());
    }
  }

  /**
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * @param UpdateTelephonePlanRequest $request
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function update(UpdateTelephonePlanRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
