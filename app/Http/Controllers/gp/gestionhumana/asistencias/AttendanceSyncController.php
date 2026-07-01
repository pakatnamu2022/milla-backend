<?php

namespace App\Http\Controllers\gp\gestionhumana\asistencias;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\asistencias\AttendanceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceSyncController extends Controller
{
  public function __construct(protected AttendanceSyncService $service)
  {
  }

  public function index(Request $request): JsonResponse
  {
    return $this->service->list($request);
  }

  public function show(string $id): JsonResponse
  {
    return $this->service->show($id);
  }

  public function sync(Request $request): JsonResponse
  {
    return $this->service->sync($request);
  }

  public function reportSunafil(Request $request): Response|JsonResponse|BinaryFileResponse
  {
    return $this->service->reportSunafil($request);
  }

  public function reportInternal(Request $request): Response|JsonResponse|BinaryFileResponse
  {
    return $this->service->reportInternal($request);
  }

  public function personDashboard(int $person_id, Request $request): JsonResponse
  {
    return $this->service->personDashboard($person_id, $request);
  }

  public function reportAbsent(Request $request): JsonResponse
  {
    return $this->service->reportAbsent($request);
  }
}
