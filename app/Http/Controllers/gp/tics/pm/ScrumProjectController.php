<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumProjectRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumProjectRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumProjectRequest;
use App\Http\Services\gp\tics\pm\ScrumProjectService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScrumProjectController extends Controller
{
  public function __construct(private ScrumProjectService $service) {}

  public function index(IndexScrumProjectRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreScrumProjectRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateScrumProjectRequest $request, int $id): JsonResponse
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
      return $this->success(['message' => 'Proyecto eliminado correctamente.']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
