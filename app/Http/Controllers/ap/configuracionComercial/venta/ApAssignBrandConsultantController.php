<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApAssignBrandConsultantRequest;
use App\Http\Requests\ap\configuracionComercial\venta\ShowGroupedApAssignBrandConsultant;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApAssignBrandConsultantRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApAssignBrandConsultantRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApAssignBrandConsultantService;
use App\Models\ap\configuracionComercial\venta\ApAssignBrandConsultant;
use Illuminate\Http\Request;

class ApAssignBrandConsultantController extends Controller
{
  protected ApAssignBrandConsultantService $service;

  public function __construct(ApAssignBrandConsultantService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApAssignBrandConsultantRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApAssignBrandConsultantRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function showGrouped(ShowGroupedApAssignBrandConsultant $request)
  {
    try {
      return $this->success($this->service->showGrouped($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateApAssignBrandConsultantRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['sede_id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(ApAssignBrandConsultant $apAssignBrandConsultant)
  {
    //
  }
}
