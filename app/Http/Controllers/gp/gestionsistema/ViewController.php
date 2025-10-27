<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexViewRequest;
use App\Http\Requests\StoreViewRequest;
use App\Http\Requests\UpdateViewRequest;
use App\Http\Services\gp\gestionsistema\ViewService;
use Illuminate\Http\Request;

class ViewController extends Controller
{

  protected ViewService $service;

  public function __construct(ViewService $service)
  {
    $this->service = $service;
  }

  public function index(IndexViewRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreViewRequest $request)
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
      return response()->json($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateViewRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function viewsWithPermissions(Request $request)
  {
    try {
      return $this->success($this->service->getViewsWithPermissions($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
