<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\personal\WorkScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
  public function __construct(protected WorkScheduleService $service) {}

  public function index(Request $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function store(Request $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->all()), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(Request $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update($request->all(), $id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function assignOne(Request $request, int $workerId): JsonResponse
  {
    try {
      return $this->success($this->service->assignOne($request->all(), $workerId));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function assignBulk(Request $request): JsonResponse
  {
    try {
      return $this->success($this->service->assignBulk($request->all()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
