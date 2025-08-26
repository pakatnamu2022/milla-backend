<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApCommercialMastersRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApCommercialMastersRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApCommercialMastersRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApCommercialMastersService;
use App\Models\ap\configuracionComercial\vehiculo\ApCommercialMasters;
use Illuminate\Http\Request;

class ApCommercialMastersController extends Controller
{
  protected ApCommercialMastersService $service;

  public function __construct(ApCommercialMastersService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApCommercialMastersRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApCommercialMastersRequest $request)
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

  public function update(UpdateApCommercialMastersRequest $request, $id)
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
}
