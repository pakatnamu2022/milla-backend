<?php

namespace App\Http\Controllers\gp\gestionhumana\contratos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\contratos\IndexContractTypeRequest;
use App\Http\Requests\gp\gestionhumana\contratos\StoreContractTypeRequest;
use App\Http\Requests\gp\gestionhumana\contratos\UpdateContractTypeRequest;
use App\Http\Services\gp\gestionhumana\contratos\ContractTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractTypeController extends Controller
{
  public function __construct(protected ContractTypeService $service) {}

  public function index(IndexContractTypeRequest $request): JsonResponse
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

  public function store(StoreContractTypeRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateContractTypeRequest $request, int $id): JsonResponse
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
      return $this->success(['message' => 'Tipo de contrato anulado.']);
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
}
