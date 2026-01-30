<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexAppointmentPlanningRequest;
use App\Http\Requests\ap\postventa\taller\StoreAppointmentPlanningRequest;
use App\Http\Requests\ap\postventa\taller\UpdateAppointmentPlanningRequest;
use App\Http\Services\ap\postventa\taller\AppointmentPlanningService;

class AppointmentPlanningController extends Controller
{
  protected AppointmentPlanningService $service;

  public function __construct(AppointmentPlanningService $service)
  {
    $this->service = $service;
  }

  public function index(IndexAppointmentPlanningRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreAppointmentPlanningRequest $request)
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

  public function update(UpdateAppointmentPlanningRequest $request, $id)
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

  public function availableSlots(IndexAppointmentPlanningRequest $request)
  {
    try {
      return $this->service->getAvailableSlots($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function downloadPDF($id)
  {
    try {
      return $this->service->generateAppointmentPDF($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
