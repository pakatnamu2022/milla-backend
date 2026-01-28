<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApSupplierOrderRequest;
use App\Http\Requests\ap\postventa\taller\StoreApSupplierOrderRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApSupplierOrderRequest;
use App\Http\Services\ap\postventa\taller\ApSupplierOrderService;
use Illuminate\Http\Request;

class ApSupplierOrderController extends Controller
{
  protected ApSupplierOrderService $service;

  public function __construct(ApSupplierOrderService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApSupplierOrderRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApSupplierOrderRequest $request)
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

  public function update(UpdateApSupplierOrderRequest $request, $id)
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

  public function markAsTaken($id)
  {
    try {
      return $this->service->markAsTaken($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updateStatus(Request $request, $id)
  {
    try {
      $status = $request->input('status');

      if (!$status) {
        return $this->error('El campo status es obligatorio');
      }

      return $this->service->updateStatus($id, $status);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}