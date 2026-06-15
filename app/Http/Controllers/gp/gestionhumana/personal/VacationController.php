<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\personal\IndexVacationRequest;
use App\Http\Requests\gp\gestionhumana\personal\StoreVacationRequest;
use App\Http\Requests\gp\gestionhumana\personal\UpdateVacationRequest;
use App\Http\Services\gp\gestionhumana\personal\VacationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacationController extends Controller
{
  public function __construct(protected VacationService $service) {}

  public function index(IndexVacationRequest $request): JsonResponse
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

  public function store(StoreVacationRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateVacationRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update($request->validated(), $id));
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

  public function approveJefatura(Request $request, int $id): JsonResponse
  {
    try {
      $userId = $request->user()?->id;
      return $this->success($this->service->approveJefatura($id, $userId));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function approveRrhh(Request $request, int $id): JsonResponse
  {
    try {
      $userId = $request->user()?->id;
      return $this->success($this->service->approveRrhh($id, $userId));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
