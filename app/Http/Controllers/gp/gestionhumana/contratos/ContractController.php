<?php

namespace App\Http\Controllers\gp\gestionhumana\contratos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\contratos\IndexContractRequest;
use App\Http\Requests\gp\gestionhumana\contratos\StoreContractRequest;
use App\Http\Requests\gp\gestionhumana\contratos\UpdateContractRequest;
use App\Http\Services\gp\gestionhumana\contratos\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
  public function __construct(protected ContractService $service) {}

  public function index(IndexContractRequest $request): JsonResponse
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

  public function store(StoreContractRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateContractRequest $request, int $id): JsonResponse
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
      return $this->success(['message' => 'Contrato anulado.']);
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

  public function pdf(int $id)
  {
    try {
      return $this->service->pdf($id);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
