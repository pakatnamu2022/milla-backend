<?php

namespace App\Http\Controllers\gp\gestionhumana\permiso;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\permiso\StoreTrabajadorPermisoRequest;
use App\Http\Requests\gp\gestionhumana\permiso\UpdateTrabajadorPermisoRequest;
use App\Http\Services\gp\gestionhumana\permiso\TrabajadorPermisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrabajadorPermisoController extends Controller
{
  public function __construct(protected TrabajadorPermisoService $service) {}

  public function index(Request $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreTrabajadorPermisoRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateTrabajadorPermisoRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update($request, $id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
