<?php

namespace App\Http\Controllers\ap\comercial;

use App\Exports\ap\comercial\VehicleSalesMatrixExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\VehicleSalesMatrixRequest;
use App\Http\Services\ap\comercial\ApVehicleSalesMatrixService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApVehicleSalesMatrixController extends Controller
{
  protected ApVehicleSalesMatrixService $service;

  public function __construct(ApVehicleSalesMatrixService $service)
  {
    $this->service = $service;
  }

  public function index(VehicleSalesMatrixRequest $request): JsonResponse
  {
    try {
      return $this->success($this->generate($request));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function export(VehicleSalesMatrixRequest $request): BinaryFileResponse
  {
    try {
      $report = $this->generate($request);

      return Excel::download(
        new VehicleSalesMatrixExport($report),
        'Reporte_Ventas_Mensual_' . $report['year'] . '.xlsx'
      );
    } catch (\Throwable $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }

  private function generate(VehicleSalesMatrixRequest $request): array
  {
    return $this->service->generate(
      (int)$request->input('year'),
      $request->input('shop_id'),
      $request->input('sede_id')
    );
  }
}
