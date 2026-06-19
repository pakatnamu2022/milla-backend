<?php

namespace App\Http\Controllers\gp\tics\pm;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\pm\IndexScrumItemRequest;
use App\Http\Requests\gp\tics\pm\StoreScrumItemRequest;
use App\Http\Requests\gp\tics\pm\UpdateScrumItemRequest;
use App\Http\Services\gp\tics\pm\ScrumItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScrumItemController extends Controller
{
  public function __construct(private ScrumItemService $service) {}

  public function index(IndexScrumItemRequest $request): JsonResponse
  {
    return response()->json($this->service->list($request));
  }

  public function kanban(Request $request, int $sprintId): JsonResponse
  {
    return response()->json($this->service->kanban($sprintId));
  }

  public function backlog(Request $request, int $projectId): JsonResponse
  {
    return response()->json($this->service->backlog($projectId));
  }

  public function show(int $id): JsonResponse
  {
    return response()->json($this->service->show($id));
  }

  public function store(StoreScrumItemRequest $request): JsonResponse
  {
    return response()->json($this->service->store($request->validated()), 201);
  }

  public function update(UpdateScrumItemRequest $request, int $id): JsonResponse
  {
    $data = $request->validated();
    $data['id'] = $id;
    return response()->json($this->service->update($data));
  }

  public function reorder(Request $request): JsonResponse
  {
    $request->validate([
      'items'      => 'required|array',
      'items.*'    => 'integer|exists:scrum_items,id',
      'sprint_id'  => 'nullable|integer|exists:scrum_sprints,id',
      'project_id' => 'required|integer|exists:scrum_projects,id',
    ]);
    $this->service->reorder($request->items, $request->sprint_id, $request->project_id);
    return response()->json(['message' => 'Orden actualizado correctamente.']);
  }

  public function destroy(int $id): JsonResponse
  {
    $this->service->destroy($id);
    return response()->json(['message' => 'Item eliminado correctamente.']);
  }

  public function toggleWatcher(int $id): JsonResponse
  {
    $watching = $this->service->toggleWatcher($id);
    return response()->json(['watching' => $watching]);
  }
}
