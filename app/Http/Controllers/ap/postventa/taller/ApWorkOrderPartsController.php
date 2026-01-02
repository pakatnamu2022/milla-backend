<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApWorkOrderPartsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApWorkOrderPartsRequest;
use App\Http\Requests\ap\postventa\taller\StoreBulkFromQuotationRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApWorkOrderPartsRequest;
use App\Http\Services\ap\postventa\taller\ApWorkOrderPartsService;

class ApWorkOrderPartsController extends Controller
{
  protected ApWorkOrderPartsService $service;

  public function __construct(ApWorkOrderPartsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApWorkOrderPartsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApWorkOrderPartsRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateApWorkOrderPartsRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener cotización activa por vehicle_id
   * GET /api/ap-work-order-parts/quotation-by-vehicle/{vehicle_id}
   */
  public function getQuotationByVehicle($vehicleId)
  {
    try {
      $quotation = $this->service->getQuotationByVehicle($vehicleId);
      return $this->success($quotation);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Guardar masivamente repuestos desde una cotización
   * POST /api/ap-work-order-parts/store-bulk-from-quotation
   * Body: { quotation_id, work_order_id, warehouse_id, group_number, quotation_detail_ids[] }
   */
  public function storeBulkFromQuotation(StoreBulkFromQuotationRequest $request)
  {
    try {
      $validated = $request->validated();
      return $this->success($this->service->storeBulkFromQuotation($validated));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
