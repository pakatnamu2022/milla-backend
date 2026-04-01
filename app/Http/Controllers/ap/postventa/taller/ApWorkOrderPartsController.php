<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApWorkOrderPartsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApWorkOrderPartsRequest;
use App\Http\Requests\ap\postventa\taller\StoreBulkFromQuotationRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApWorkOrderPartsRequest;
use App\Http\Services\ap\postventa\taller\ApWorkOrderPartsService;
use Illuminate\Http\Request;

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

  public function storeBulkFromQuotation(StoreBulkFromQuotationRequest $request)
  {
    try {
      $validated = $request->validated();
      return $this->success($this->service->storeBulkFromQuotation($validated));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function assignToTechnician($id, Request $request)
  {
    try {
      $data = $request->validate([
        'delivered_to' => 'required|integer|exists:rrhh_persona,id',
        'delivered_quantity' => 'required|numeric|min:0.01',
      ]);

      return $this->success($this->service->assignToTechnician($id, $data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function confirmReceipt(Request $request)
  {
    try {
      $data = $request->validate([
        'delivery_ids' => 'required|array|min:1',
        'delivery_ids.*' => 'required|integer|distinct|exists:ap_work_order_part_deliveries,id',
      ]);

      return $this->success($this->service->confirmReceipt($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getAssignmentsByWorkOrder($workOrderId)
  {
    try {
      return $this->success($this->service->getAssignmentsByWorkOrder($workOrderId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getDeliveries($id)
  {
    try {
      return $this->success($this->service->getDeliveriesByWorkOrderPart($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
