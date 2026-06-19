<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumProjectRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumProjectRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumProjectRequest;
use App\Http\Services\gp\tics\pm\ScrumProjectService;
use Illuminate\Http\JsonResponse;

class ScrumProjectController extends Controller
{
  public function __construct(private ScrumProjectService $service) {}

  public function index(IndexScrumProjectRequest $request): JsonResponse
  {
    return response()->json($this->service->list($request));
  }

  public function show(int $id): JsonResponse
  {
    return response()->json($this->service->show($id));
  }

  public function store(StoreScrumProjectRequest $request): JsonResponse
  {
    $data = $request->validated();
    $data['created_by'] = auth()->id();
    return response()->json($this->service->store($data), 201);
  }

  public function update(UpdateScrumProjectRequest $request, int $id): JsonResponse
  {
    $data = $request->validated();
    $data['id'] = $id;
    return response()->json($this->service->update($data));
  }

  public function destroy(int $id): JsonResponse
  {
    $this->service->destroy($id);
    return response()->json(['message' => 'Proyecto eliminado correctamente.']);
  }
}
