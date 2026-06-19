<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumTagRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumTagRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumTagRequest;
use App\Http\Services\gp\tics\pm\ScrumTagService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScrumTagController extends Controller
{
  public function __construct(private ScrumTagService $service) {}

  public function index(IndexScrumTagRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreScrumTagRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateScrumTagRequest $request, int $id): JsonResponse
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
      return $this->success(['message' => 'Tag eliminado correctamente.']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
