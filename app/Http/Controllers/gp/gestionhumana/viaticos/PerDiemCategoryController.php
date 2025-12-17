<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemCategoryRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemCategoryRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemCategoryRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemCategoryResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemCategoryService;
use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use Exception;
use Illuminate\Http\JsonResponse;

class PerDiemCategoryController extends Controller
{
  protected PerDiemCategoryService $service;

  public function __construct(PerDiemCategoryService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPerDiemCategoryRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StorePerDiemCategoryRequest $request)
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

  public function update(UpdatePerDiemCategoryRequest $request, $id)
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
