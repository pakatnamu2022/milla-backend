<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemRateRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemRateRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemRateRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRateResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemRateService;
use Throwable;

class PerDiemRateController extends Controller
{
  protected PerDiemRateService $service;

  public function __construct(PerDiemRateService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of per diem rates
   */
  public function index(IndexPerDiemRateRequest $request)
  {
    try {
      return $this->service->index($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created per diem rate
   */
  public function store(StorePerDiemRateRequest $request)
  {
    try {
      $rate = $this->service->store($request->validated());
      return $this->success([
        'data' => new PerDiemRateResource($rate),
        'message' => 'Tarifa de viático creada exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified per diem rate
   */
  public function show(int $id)
  {
    try {
      $rate = $this->service->show($id);
      if (!$rate) {
        return $this->error('Tarifa de viático no encontrada');
      }
      return $this->success(new PerDiemRateResource($rate));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified per diem rate
   */
  public function update(UpdatePerDiemRateRequest $request, int $id)
  {
    try {
      $rate = $this->service->update($id, $request->validated());
      return $this->success([
        'data' => new PerDiemRateResource($rate),
        'message' => 'Tarifa de viático actualizada exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified per diem rate
   */
  public function destroy(int $id)
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Tarifa de viático eliminada exitosamente']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
