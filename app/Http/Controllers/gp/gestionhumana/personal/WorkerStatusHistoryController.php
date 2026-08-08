<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\personal\IndexWorkerStatusHistoryRequest;
use App\Http\Requests\gp\gestionhumana\personal\StoreWorkerStatusHistoryRequest;
use App\Http\Services\gp\gestionhumana\personal\WorkerStatusHistoryService;
use Illuminate\Http\JsonResponse;

class WorkerStatusHistoryController extends Controller
{
  public function __construct(protected WorkerStatusHistoryService $service) {}

  public function index(IndexWorkerStatusHistoryRequest $request): JsonResponse
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

  public function store(StoreWorkerStatusHistoryRequest $request): JsonResponse
  {
    try {
      $userId = $request->user()?->id;
      return $this->success($this->service->store($request->validated(), $userId), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function currentStatus(int $workerId): JsonResponse
  {
    try {
      return $this->success($this->service->currentStatus($workerId));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
