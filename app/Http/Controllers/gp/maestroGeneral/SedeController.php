<?php

namespace App\Http\Controllers\gp\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\maestroGeneral\IndexSedeRequest;
use App\Http\Requests\gp\maestroGeneral\StoreSedeRequest;
use App\Http\Requests\gp\maestroGeneral\UpdateSedeRequest;
use App\Http\Services\gp\maestroGeneral\SedeService;
use Illuminate\Http\Request;

class SedeController extends Controller
{
  protected SedeService $service;

  public function __construct(SedeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexSedeRequest $request)
  {
    return $this->service->list(request());
  }

  public function mySedes(Request $request)
  {
    try {
      return $this->success($this->service->getMySedes($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function availableLocationsShop(IndexSedeRequest $request)
  {
    return $this->service->getAvailableLocationsShop(request());
  }

  public function store(StoreSedeRequest $request)
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

  public function update(UpdateSedeRequest $request, $id)
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
