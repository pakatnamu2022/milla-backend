<?php

namespace App\Http\Controllers\ap\compras;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\compras\ApprovePurchaseReceptionRequest;
use App\Http\Requests\ap\compras\IndexPurchaseReceptionRequest;
use App\Http\Requests\ap\compras\StorePurchaseReceptionRequest;
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
     * Approve or reject a purchase reception
     */
    public function approve(ApprovePurchaseReceptionRequest $request, $id)
    {
        try {
            $data = $request->validated();
            return $this->success($this->service->approveReception(
                $id,
                $data['approved'],
                $data['notes'] ?? null
            ));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Get pending review receptions
     */
    public function pendingReview()
    {
        try {
            return $this->success($this->service->getPendingReview());
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
}
