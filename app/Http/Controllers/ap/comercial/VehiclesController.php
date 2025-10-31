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
use Illuminate\Http\Request;
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
   * Export vehicles data
   * @param Request $request
   * @return JsonResponse
   */
  public function export(Request $request)
  {
    try {
//      return $this->service->export($request);
      return true;
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display a listing of vehicles with filters
   *
   * @param IndexVehiclesRequest $request
   * @return JsonResponse
   */
  public function index(IndexVehiclesRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
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
      return $this->success($this->service->store($request->validated()));
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
      return $this->success($this->service->show($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display all vehicles with costs data (without movements)
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function getCostsData(Request $request): JsonResponse
  {
    try {
      return $this->service->listWithCosts($request);
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
      return $this->success($this->service->update($data));
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
      ;
      return $this->success($this->service->destroy($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
