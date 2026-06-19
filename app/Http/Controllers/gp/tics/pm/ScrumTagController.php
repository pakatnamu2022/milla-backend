<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumTagRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumTagRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumTagRequest;
use App\Http\Services\gp\tics\pm\ScrumTagService;
use Illuminate\Http\JsonResponse;

class ScrumTagController extends Controller
{
  public function __construct(private ScrumTagService $service) {}

  public function index(IndexScrumTagRequest $request): JsonResponse
  {
    return response()->json($this->service->list($request));
  }

  public function store(StoreScrumTagRequest $request): JsonResponse
  {
    return response()->json($this->service->store($request->validated()), 201);
  }

  public function update(UpdateScrumTagRequest $request, int $id): JsonResponse
  {
    $data = $request->validated();
    $data['id'] = $id;
    return response()->json($this->service->update($data));
  }

  public function destroy(int $id): JsonResponse
  {
    $this->service->destroy($id);
    return response()->json(['message' => 'Tag eliminado correctamente.']);
  }
}
