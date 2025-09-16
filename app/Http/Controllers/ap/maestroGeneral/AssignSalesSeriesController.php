<?php

namespace App\Http\Controllers\ap\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\maestroGeneral\IndexAssignSalesSeriesRequest;
use App\Http\Requests\ap\maestroGeneral\StoreAssignSalesSeriesRequest;
use App\Http\Requests\ap\maestroGeneral\UpdateAssignSalesSeriesRequest;
use App\Http\Services\ap\maestroGeneral\AssignSalesSeriesService;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use Illuminate\Http\Request;

class AssignSalesSeriesController extends Controller
{
  protected AssignSalesSeriesService $service;

  public function __construct(AssignSalesSeriesService $service)
  {
    $this->service = $service;
  }

  public function index(IndexAssignSalesSeriesRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreAssignSalesSeriesRequest $request)
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

  public function update(UpdateAssignSalesSeriesRequest $request, $id)
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
