<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumSprintRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumSprintRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumSprintRequest;
use App\Http\Services\gp\tics\pm\ScrumSprintService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScrumSprintController extends Controller
{
  public function __construct(private ScrumSprintService $service) {}

  public function index(IndexScrumSprintRequest $request): JsonResponse
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

  public function store(StoreScrumSprintRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateScrumSprintRequest $request, int $id): JsonResponse
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
      return $this->success(['message' => 'Sprint eliminado correctamente.']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function activate(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->activate($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function close(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->close($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
