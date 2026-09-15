<?php

namespace App\Http\Controllers\gp\gestionhumana\contratos;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\contratos\ContractSignatureService;
use App\Models\gp\gestionhumana\contratos\Contract;
use Illuminate\Http\JsonResponse;

/**
 * Acciones del flujo de firma de contratos accedidas por enlace de correo (sin
 * autenticación), protegidas con firma de URL de Laravel (`signed` middleware)
 * en vez del token/URL sin protección del legacy — ver
 * ContractSignatureService::requestApproval/approveByHr/sendToWorker, que
 * generan estos enlaces con URL::temporarySignedRoute().
 */
class PublicContractSignatureController extends Controller
{
  public function __construct(protected ContractSignatureService $service) {}

  public function approve(Contract $contract): JsonResponse
  {
    try {
      return $this->success($this->service->approveByHr($contract));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function sign(Contract $contract): JsonResponse
  {
    try {
      return $this->success($this->service->sign($contract));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function confirmReading(Contract $contract): JsonResponse
  {
    try {
      return $this->success($this->service->confirmReading($contract));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
