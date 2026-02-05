<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\StoreWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\UpdateWorkOrderRequest;
use App\Http\Services\ap\postventa\taller\WorkOrderService;
use Exception;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
  protected WorkOrderService $service;

  public function __construct(WorkOrderService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWorkOrderRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreWorkOrderRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
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

  public function update(UpdateWorkOrderRequest $request, $id)
  {
    try {
      $data = $request->validated();
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

  public function getPaymentSummary($id, Request $request)
  {
    try {
      $groupNumber = $request->query('group_number');
      return $this->service->getPaymentSummary($id, $groupNumber);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getPreLiquidationPdf($id)
  {
    try {
      return $this->service->getPreLiquidationPdf($id);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al generar el preliquidación',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function unlinkQuotation($id)
  {
    try {
      return $this->success($this->service->unlinkQuotation($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function authorization(Request $request, $id)
  {
    try {
      $data = $request->validate(
        [
          'allow_remove_associated_quote' => 'sometimes|required|boolean:',
          'allow_editing_inspection' => 'sometimes|required|boolean'
        ]
      );
      $data['id'] = $id;
      return $this->success($this->service->authorization($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function invoiceTo(Request $request, $id)
  {
    try {
      $data = $request->validate(
        [
          'invoice_to' => 'required|integer|exists:business_partners,id',
        ]
      );
      $data['id'] = $id;
      return $this->success($this->service->invoiceTo($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
