<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexSedeRequest;
use App\Http\Requests\gp\gestionsistema\StoreSedeRequest;
use App\Http\Requests\gp\gestionsistema\UpdateSedeRequest;
use App\Http\Services\gp\gestionsistema\SedeService;
use App\Models\gp\gestionsistema\Sede;
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

  public function assignedSalesWorkers(Request $request)
  {
    // esto es para obtener los trabajadores asignados a ventas es diferente a trabajadores por sede
    return $this->service->getWorkers($request);
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
