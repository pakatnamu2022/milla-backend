<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApTractionTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApTractionTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApTractionTypeRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApTractionTypeService;
use App\Models\ap\configuracionComercial\vehiculo\ApTractionType;
use Illuminate\Http\Request;

class ApTractionTypeController extends Controller
{
  protected ApTractionTypeService $service;

  public function __construct(ApTractionTypeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApTractionTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApTractionTypeRequest $request)
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

  public function update(UpdateApTractionTypeRequest $request, $id)
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
