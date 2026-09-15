<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\marketing\IndexMktPurchaseOrderRequest;
use App\Http\Requests\ap\marketing\StoreMktPurchaseOrderRequest;
use App\Http\Requests\ap\marketing\UpdateMktPurchaseOrderRequest;
use App\Http\Services\ap\marketing\MktPurchaseOrderService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use Illuminate\Http\Request;

class MktPurchaseOrderController extends Controller
{
  protected MktPurchaseOrderService $service;
  protected DigitalFileService $digitalFileService;

  public function __construct(MktPurchaseOrderService $service, DigitalFileService $digitalFileService)
  {
    $this->service = $service;
    $this->digitalFileService = $digitalFileService;
  }

  public function index(IndexMktPurchaseOrderRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreMktPurchaseOrderRequest $request)
  {
    try {
      $data = $request->validated();

      if ($request->hasFile('file')) {
        $uploaded = $this->digitalFileService->store($request->file('file'), '/ap/marketing/purchase-orders/');
        $data['file_path'] = $uploaded->url;
      }
      unset($data['file']);

      return $this->success($this->service->store($data));
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

  public function update(UpdateMktPurchaseOrderRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      if ($request->hasFile('file')) {
        $uploaded = $this->digitalFileService->store($request->file('file'), '/ap/marketing/purchase-orders/');
        $data['file_path'] = $uploaded->url;
      }
      unset($data['file']);

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

  public function changeStatus(Request $request, $id)
  {
    try {
      $status = $request->input('status');
      $extra  = $request->only(['electronic_document_id']);
      return $this->success($this->service->changeStatus($id, $status, $extra));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
