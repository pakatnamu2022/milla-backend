<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexVehiclesRequest;
use App\Http\Requests\ap\comercial\StoreVehiclesRequest;
use App\Http\Requests\ap\comercial\UpdateVehiclesRequest;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Services\ap\comercial\VehicleService;
use App\Http\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class VehiclesController extends Controller
{
  use HasApiResponse;

  protected VehicleService $service;

  public function __construct(VehicleService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of vehicles with filters
   *
   * @param IndexVehiclesRequest $request
   * @return JsonResponse
   */
  public function index(IndexVehiclesRequest $request): JsonResponse
  {
    return $this->service->list($request);
  }

  /**
   * Store a newly created vehicle
   *
   * @param StoreVehiclesRequest $request
   * @return JsonResponse
   */
  public function store(StoreVehiclesRequest $request): JsonResponse
  {
    try {
      $vehicle = $this->service->store($request->validated());
      return $this->success(new VehiclesResource($vehicle), 'Vehículo creado exitosamente', 201);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified vehicle
   *
   * @param int $id
   * @return JsonResponse
   */
  public function show(int $id): JsonResponse
  {
    try {
      $vehicle = $this->service->find($id);
      $vehicle->load([
        'model',
        'color',
        'engineType',
        'status',
        'sede',
        'warehousePhysical',
        'vehicleMovements.status',
        'vehicleMovements.user'
      ]);
      return $this->success(new VehiclesResource($vehicle));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified vehicle
   *
   * @param UpdateVehiclesRequest $request
   * @param int $id
   * @return JsonResponse
   */
  public function update(UpdateVehiclesRequest $request, int $id): JsonResponse
  {
    try {
      $data = array_merge($request->validated(), ['id' => $id]);
      $vehicle = $this->service->update($data);
      return $this->success(new VehiclesResource($vehicle), 'Vehículo actualizado exitosamente');
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified vehicle (soft delete)
   *
   * @param int $id
   * @return JsonResponse
   */
  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(null, 'Vehículo eliminado exitosamente');
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
