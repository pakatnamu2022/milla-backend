<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\StoreCompanyBranchRequest;
use App\Http\Requests\gp\gestionsistema\UpdateCompanyBranchRequest;
use App\Http\Services\gp\gestionsistema\CompanyBranchService;
use App\Models\gp\gestionsistema\CompanyBranch;
use Illuminate\Http\Request;

class CompanyBranchController extends Controller
{
  protected CompanyBranchService $service;

  public function __construct(CompanyBranchService $service)
  {
    $this->service = $service;
  }

  public function index()
  {
    return $this->service->list(request());
  }

  public function store(StoreCompanyBranchRequest $request)
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

  public function update(UpdateCompanyBranchRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
