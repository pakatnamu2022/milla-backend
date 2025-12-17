<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemPolicyRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemPolicyRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemPolicyRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemPolicyResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemPolicyService;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use Illuminate\Http\Request;

class PerDiemPolicyController extends Controller
{
  protected PerDiemPolicyService $service;

  public function __construct(PerDiemPolicyService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPerDiemPolicyRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StorePerDiemPolicyRequest $request)
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

  public function update(UpdatePerDiemPolicyRequest $request, $id)
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
