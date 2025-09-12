<?php

namespace App\Http\Controllers\ap\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\maestroGeneral\IndexTaxClassTypesRequest;
use App\Http\Requests\ap\maestroGeneral\StoreTaxClassTypesRequest;
use App\Http\Requests\ap\maestroGeneral\UpdateTaxClassTypesRequest;
use App\Http\Services\ap\maestroGeneral\TaxClassTypesService;
use App\Models\ap\maestroGeneral\TaxClassTypes;
use Illuminate\Http\Request;

class TaxClassTypesController extends Controller
{
  protected TaxClassTypesService $service;

  public function __construct(TaxClassTypesService $service)
  {
    $this->service = $service;
  }

  public function index(IndexTaxClassTypesRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreTaxClassTypesRequest $request)
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

  public function update(UpdateTaxClassTypesRequest $request, $id)
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
