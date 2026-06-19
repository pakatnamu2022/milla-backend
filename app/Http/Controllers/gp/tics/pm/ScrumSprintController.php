<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumSprintRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumSprintRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumSprintRequest;
use App\Http\Services\gp\tics\pm\ScrumSprintService;
use Illuminate\Http\JsonResponse;

class ScrumSprintController extends Controller
{
  public function __construct(private ScrumSprintService $service) {}

  public function index(IndexScrumSprintRequest $request): JsonResponse
  {
    return response()->json($this->service->list($request));
  }

  public function show(int $id): JsonResponse
  {
    return response()->json($this->service->show($id));
  }

  public function store(StoreScrumSprintRequest $request): JsonResponse
  {
    return response()->json($this->service->store($request->validated()), 201);
  }

  public function update(UpdateScrumSprintRequest $request, int $id): JsonResponse
  {
    $data = $request->validated();
    $data['id'] = $id;
    return response()->json($this->service->update($data));
  }

  public function destroy(int $id): JsonResponse
  {
    $this->service->destroy($id);
    return response()->json(['message' => 'Sprint eliminado correctamente.']);
  }

  public function activate(int $id): JsonResponse
  {
    return response()->json($this->service->activate($id));
  }

  public function close(int $id): JsonResponse
  {
    return response()->json($this->service->close($id));
  }
}
