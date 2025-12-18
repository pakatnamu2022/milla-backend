<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexApExhibitionVehiclesRequest;
use App\Http\Requests\ap\comercial\StoreApExhibitionVehiclesRequest;
use App\Http\Requests\ap\comercial\UpdateApExhibitionVehiclesRequest;
use App\Http\Services\ap\comercial\ApExhibitionVehiclesService;
use App\Http\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ApExhibitionVehiclesController extends Controller
{
  use HasApiResponse;

  protected ApExhibitionVehiclesService $service;

  public function __construct(ApExhibitionVehiclesService $service)
  {
    $this->service = $service;
  }

  /**
   * Export exhibition vehicles data
   */
  public function export(Request $request): JsonResponse
  {
    try {
      return $this->service->exportExhibitionVehicles($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display a listing of exhibition vehicles with filters
   */
  public function index(IndexApExhibitionVehiclesRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created exhibition vehicle
   */
  public function store(StoreApExhibitionVehiclesRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified exhibition vehicle
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
   * Update the specified exhibition vehicle
   */
  public function update(UpdateApExhibitionVehiclesRequest $request, int $id): JsonResponse
  {
    try {
      $data = array_merge($request->validated(), ['id' => $id]);
      return $this->success($this->service->update($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified exhibition vehicle (soft delete)
   */
  public function destroy(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
