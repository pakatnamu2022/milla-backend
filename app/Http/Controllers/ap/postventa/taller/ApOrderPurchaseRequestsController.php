<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApOrderPurchaseRequestsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApOrderPurchaseRequestsRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApOrderPurchaseRequestsRequest;
use App\Http\Services\ap\postventa\taller\ApOrderPurchaseRequestsService;
use Illuminate\Http\Request;

class ApOrderPurchaseRequestsController extends Controller
{
  protected ApOrderPurchaseRequestsService $service;

  public function __construct(ApOrderPurchaseRequestsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApOrderPurchaseRequestsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApOrderPurchaseRequestsRequest $request)
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

  public function update(UpdateApOrderPurchaseRequestsRequest $request, $id)
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
   * Obtener detalles de solicitudes pendientes para crear órdenes de compra
   * GET /api/purchase-requests/pending-details
   */
  public function getPendingDetails(Request $request)
  {
    try {
      return $this->service->getPendingDetails($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Rechazar/descartar un detalle de solicitud
   * PATCH /api/purchase-requests/details/{id}/reject
   */
  public function rejectDetail($id)
  {
    try {
      return $this->service->rejectDetail($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Descargar PDF de la solicitud de compra
   * GET /api/ap/postVenta/orderPurchaseRequests/{id}/pdf
   */
  public function downloadPDF($id)
  {
    try {
      return $this->service->generatePurchaseRequestPDF($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
