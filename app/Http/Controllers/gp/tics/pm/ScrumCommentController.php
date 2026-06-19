<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumCommentRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumCommentRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumCommentRequest;
use App\Http\Services\gp\tics\pm\ScrumCommentService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScrumCommentController extends Controller
{
  public function __construct(private ScrumCommentService $service) {}

  public function index(IndexScrumCommentRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreScrumCommentRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateScrumCommentRequest $request, int $id): JsonResponse
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Comentario eliminado correctamente.']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
