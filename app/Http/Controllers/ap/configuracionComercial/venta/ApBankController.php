<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApBankRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApBankRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApBankRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApBankService;
use App\Models\ap\configuracionComercial\venta\ApBank;
use Illuminate\Http\Request;

class ApBankController extends Controller
{
  protected ApBankService $service;

  public function __construct(ApBankService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApBankRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApBankRequest $request)
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

  public function update(UpdateApBankRequest $request, $id)
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
}
