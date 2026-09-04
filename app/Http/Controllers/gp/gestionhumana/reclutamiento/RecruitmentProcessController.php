<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\IndexRecruitmentProcessRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\StoreRecruitmentProcessRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UpdateRecruitmentProcessRequest;
use App\Http\Services\gp\gestionhumana\reclutamiento\RecruitmentProcessService;
use Illuminate\Http\JsonResponse;

class RecruitmentProcessController extends Controller
{
  public function __construct(protected RecruitmentProcessService $service) {}

  public function index(IndexRecruitmentProcessRequest $request): JsonResponse
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

  public function store(StoreRecruitmentProcessRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateRecruitmentProcessRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update($id, $request->validated()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function close(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->close($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Proceso anulado.']);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
