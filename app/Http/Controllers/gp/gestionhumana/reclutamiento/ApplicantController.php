<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\ChangeApplicantStatusRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\IndexApplicantRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\StoreApplicantRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UpdateApplicantRequest;
use App\Http\Services\gp\gestionhumana\reclutamiento\ApplicantService;
use Illuminate\Http\JsonResponse;

class ApplicantController extends Controller
{
  public function __construct(protected ApplicantService $service) {}

  public function index(IndexApplicantRequest $request): JsonResponse
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

  public function store(StoreApplicantRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store(
        $request->validated(),
        $request->file('file_cv'),
        $request->file('file_foto'),
      ));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateApplicantRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update(
        $id,
        $request->validated(),
        $request->file('file_cv'),
        $request->file('file_foto'),
      ));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function changeStatus(ChangeApplicantStatusRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->changeStatus($id, $request->validated()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Postulante anulado.']);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
