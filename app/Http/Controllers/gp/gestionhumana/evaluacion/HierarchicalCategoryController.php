<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexHierarchicalCategoryRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreHierarchicalCategoryRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateHierarchicalCategoryRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\HierarchicalCategoryService;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Illuminate\Http\Request;

class HierarchicalCategoryController extends Controller
{

  protected HierarchicalCategoryService $service;

  public function __construct(HierarchicalCategoryService $service)
  {
    $this->service = $service;
  }

  public function index(IndexHierarchicalCategoryRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function listAll(Request $request)
  {
    try {
      $idCycle = $request->query('idCycle');
      return $this->success($this->service->listAll($idCycle));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreHierarchicalCategoryRequest $request)
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

  public function update(UpdateHierarchicalCategoryRequest $request, $id)
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
