<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\AddProcessDaysRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\IndexRecruitmentProcessRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\PauseProcessRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\StoreRecruitmentProcessRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UpdateRecruitmentProcessRequest;
use App\Http\Services\gp\gestionhumana\reclutamiento\RecruitmentProcessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function pause(PauseProcessRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->pause($id, $request->validated()['motivo']));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function resume(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->resume($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function reopen(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->reopen($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function addDays(AddProcessDaysRequest $request): JsonResponse
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->addDaysToMany($data['proceso_postulacion_ids'], $data['dias'], $data['motivo']));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function coverage(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->coverage($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function history(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->history($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
