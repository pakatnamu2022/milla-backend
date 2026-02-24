<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\ExportVehiclesSalesRequest;
use App\Http\Requests\ap\comercial\IndexVehiclesRequest;
use App\Http\Requests\ap\comercial\StoreVehiclesRequest;
use App\Http\Requests\ap\comercial\UpdateVehiclesRequest;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Services\ap\comercial\VehiclesService;
use App\Http\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VehiclesController extends Controller
{
  use HasApiResponse;

  protected VehiclesService $service;

  public function __construct(VehiclesService $service)
  {
    $this->service = $service;
  }

  /**
   * Export vehicles data
   * @param ExportVehiclesSalesRequest $request
   * @return JsonResponse
   */
  public function exportSales(ExportVehiclesSalesRequest $request)
  {
    try {
      return $this->service->exportSales($request);
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

  /**
   * Get all invoices (electronic documents) for a specific vehicle
   *
   * @param int $id
   * @return JsonResponse
   */
  public function getInvoices(int $id): JsonResponse
  {
    try {
      return $this->service->getInvoices($id);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get vehicle client debt information based on electronic documents
   *
   * @param int $id
   * @return JsonResponse
   */
  public function getVehicleClientDebtInfo(int $id): JsonResponse
  {
    try {
      return $this->service->getVehicleClientDebtInfo($id);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get the purchase order associated with a vehicle
   *
   * @param int $id
   * @return JsonResponse
   */
  public function getPurchaseOrder(int $id): JsonResponse
  {
    try {
      return $this->service->getPurchaseOrder($id);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Match VINs from an uploaded Excel file against vehicles in Inventario VN status
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function matchVins(Request $request): JsonResponse
  {
    try {
      $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
      ]);
      return $this->service->matchVins($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
