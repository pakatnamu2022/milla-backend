<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApClassArticleRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApClassArticleRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApClassArticleRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApClassArticleService;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use Illuminate\Http\Request;

class ApClassArticleController extends Controller
{
  protected ApClassArticleService $service;

  public function __construct(ApClassArticleService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApClassArticleRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApClassArticleRequest $request)
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

  public function update(UpdateApClassArticleRequest $request, $id)
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
