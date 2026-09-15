<?php

namespace App\Http\Controllers\gp\gestionhumana\contratos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\contratos\IndexContractTemplateRequest;
use App\Http\Requests\gp\gestionhumana\contratos\StoreContractTemplateRequest;
use App\Http\Requests\gp\gestionhumana\contratos\UpdateContractTemplateRequest;
use App\Http\Services\gp\gestionhumana\contratos\ContractTemplateService;
use Illuminate\Http\JsonResponse;

class ContractTemplateController extends Controller
{
  public function __construct(protected ContractTemplateService $service) {}

  public function index(IndexContractTemplateRequest $request): JsonResponse
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

  public function store(StoreContractTemplateRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateContractTemplateRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update($id, $request->validated()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Plantilla de contrato anulada.']);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
