<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumCommentRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumCommentRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumCommentRequest;
use App\Http\Services\gp\tics\pm\ScrumCommentService;
use Illuminate\Http\JsonResponse;

class ScrumCommentController extends Controller
{
  public function __construct(private ScrumCommentService $service) {}

  public function index(IndexScrumCommentRequest $request): JsonResponse
  {
    return response()->json($this->service->list($request));
  }

  public function store(StoreScrumCommentRequest $request): JsonResponse
  {
    return response()->json($this->service->store($request->validated()), 201);
  }

  public function update(UpdateScrumCommentRequest $request, int $id): JsonResponse
  {
    $data = $request->validated();
    $data['id'] = $id;
    return response()->json($this->service->update($data));
  }

  public function destroy(int $id): JsonResponse
  {
    $this->service->destroy($id);
    return response()->json(['message' => 'Comentario eliminado correctamente.']);
  }
}
