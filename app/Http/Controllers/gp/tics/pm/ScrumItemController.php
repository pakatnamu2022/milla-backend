<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumItemRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumItemRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumItemRequest;
use App\Http\Services\gp\tics\pm\ScrumItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ScrumItemController extends Controller
{
  public function __construct(private ScrumItemService $service) {}

  public function index(IndexScrumItemRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function kanban(?int $sprintId = null): JsonResponse
  {
    try {
      return $this->success($this->service->kanban($sprintId));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function backlog(int $projectId): JsonResponse
  {
    try {
      return $this->success($this->service->backlog($projectId));
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

  public function store(StoreScrumItemRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateScrumItemRequest $request, int $id): JsonResponse
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function reorder(Request $request): JsonResponse
  {
    try {
      $request->validate([
        'items'      => 'required|array',
        'items.*'    => 'integer|exists:scrum_items,id',
        'sprint_id'  => 'nullable|integer|exists:scrum_sprints,id',
        'project_id' => 'required|integer|exists:scrum_projects,id',
      ]);
      $this->service->reorder($request->items, $request->sprint_id, $request->project_id);
      return $this->success(['message' => 'Orden actualizado correctamente.']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Item eliminado correctamente.']);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function toggleWatcher(int $id): JsonResponse
  {
    try {
      return $this->success(['watching' => $this->service->toggleWatcher($id)]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
