<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\comercial\VehicleSaleStatusAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Auditoría (solo lectura) de vehículos vendidos que volvieron a inventario.
 */
class ApVehicleSaleStatusAuditController extends Controller
{
  protected VehicleSaleStatusAuditService $service;

  public function __construct(VehicleSaleStatusAuditService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request): JsonResponse
  {
    try {
      $vins = array_map('trim', (array) $request->input('vin', []));

      return $this->success($this->service->generate($vins));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
