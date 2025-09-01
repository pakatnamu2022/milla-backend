<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexDigitalFileRequest;
use App\Http\Requests\gp\gestionsistema\StoreDigitalFileRequest;
use App\Http\Requests\gp\gestionsistema\UpdateDigitalFileRequest;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use function response;

class DigitalFileController extends Controller
{
  protected DigitalFileService $service;

  public function __construct(DigitalFileService $service)
  {
    $this->service = $service;
  }


  public function index(IndexDigitalFileRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreDigitalFileRequest $request)
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->store($data['file']));
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

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
