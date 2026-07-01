<?php

namespace App\Http\Controllers\gp\gestionhumana\asistencias;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\asistencias\AttendanceExclusionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceExclusionController extends Controller
{
  public function __construct(protected AttendanceExclusionService $service) {}

  public function index(Request $request): JsonResponse
  {
    return $this->service->list($request);
  }

  public function show(int $id): JsonResponse
  {
    return $this->service->show($id);
  }

  public function store(Request $request): JsonResponse
  {
    return $this->service->store($request);
  }

  public function update(Request $request, int $id): JsonResponse
  {
    return $this->service->update($request, $id);
  }

  public function destroy(int $id): JsonResponse
  {
    return $this->service->destroy($id);
  }
}
