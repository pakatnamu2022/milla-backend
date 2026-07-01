<?php

namespace App\Http\Controllers\gp\gestionhumana\asistencias;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\asistencias\AttendanceExclusionService;
use Illuminate\Http\Request;

class AttendanceExclusionController extends Controller
{
  public function __construct(protected AttendanceExclusionService $service) {}

  public function index(Request $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(Request $request)
  {
    try {
      return $this->success($this->service->store($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(Request $request, int $id)
  {
    try {
      return $this->success($this->service->update($request, $id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id)
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
