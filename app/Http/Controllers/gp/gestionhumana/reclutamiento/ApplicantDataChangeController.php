<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\IndexApplicantDataChangeRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\RejectApplicantDataChangeRequest;
use App\Http\Services\gp\gestionhumana\reclutamiento\ApplicantDataChangeService;
use Illuminate\Http\JsonResponse;

class ApplicantDataChangeController extends Controller
{
  public function __construct(protected ApplicantDataChangeService $service) {}

  public function index(IndexApplicantDataChangeRequest $request): JsonResponse
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

  public function approve(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->approve($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function reject(RejectApplicantDataChangeRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->reject($id, $request->validated()['motivo']));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
