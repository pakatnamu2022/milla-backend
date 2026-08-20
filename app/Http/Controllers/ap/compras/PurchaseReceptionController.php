<?php

namespace App\Http\Controllers\ap\compras;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\compras\ApprovePurchaseReceptionRequest;
use App\Http\Requests\ap\compras\IndexPurchaseReceptionRequest;
use App\Http\Requests\ap\compras\MarkDefectiveProductsRequest;
use App\Http\Requests\ap\compras\StorePurchaseReceptionRequest;
use App\Http\Requests\ap\compras\UnmarkDefectiveProductRequest;
use App\Http\Requests\ap\compras\UpdatePurchaseReceptionRequest;
use App\Http\Services\ap\compras\PurchaseReceptionService;

class PurchaseReceptionController extends Controller
{
  protected PurchaseReceptionService $service;

  public function __construct(PurchaseReceptionService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of purchase receptions
   */
  public function index(IndexPurchaseReceptionRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created purchase reception
   */
  public function store(StorePurchaseReceptionRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified purchase reception
   */
  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified purchase reception
   */
  public function update(UpdatePurchaseReceptionRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified purchase reception
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get receptions by purchase order
   */
  public function byPurchaseOrder($purchaseOrderId)
  {
    try {
      return $this->success($this->service->getByPurchaseOrder($purchaseOrderId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Marca productos como defectuosos después de la facturación
   * Permite ajustar una recepción ya facturada cuando se detectan productos defectuosos
   * que generarán nota de crédito
   */
  public function markDefectiveProducts(MarkDefectiveProductsRequest $request)
  {
    try {
      $result = $this->service->markDefectiveProducts($request->validated());
      return $this->success($result);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Revierte el marcado de defectuoso de un producto
   * Pasa toda la cantidad a quantity_received y limpia is_credit_note
   */
  public function unmarkDefectiveProduct(UnmarkDefectiveProductRequest $request)
  {
    try {
      $result = $this->service->unmarkDefectiveProduct($request->input('reception_detail_id'));
      return $this->success($result);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
