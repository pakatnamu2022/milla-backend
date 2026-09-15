<?php

namespace App\Http\Controllers\gp\gestionhumana\contratos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\contratos\SignBatchRequest;
use App\Http\Services\gp\gestionhumana\contratos\ContractSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractSignatureController extends Controller
{
  public function __construct(protected ContractSignatureService $service) {}

  public function requestApproval(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->requestApproval($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function signBatch(SignBatchRequest $request): JsonResponse
  {
    try {
      $data = $request->validated();

      return $this->success($this->service->signBatch($data['lote'], $data['firmante_id']));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function sendToWorker(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->sendToWorker($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function downloadSigned(int $id)
  {
    try {
      return $this->service->downloadSigned($id);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function expiring(Request $request): JsonResponse
  {
    try {
      $days = (int) ($request->query('days', 50));

      return $this->success($this->service->expiring($days));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
