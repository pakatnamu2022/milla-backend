<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Resources\gp\tics\pm\ScrumSprintResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumSprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScrumSprintService extends BaseService implements BaseServiceInterface
{
  private const CACHE_TTL = 900; // 15 min

  public function list(Request $request)
  {
    $query = ScrumSprint::query()
      ->withCount(['items', 'items as done_count' => fn($q) => $q->where('status', 'hecho')])
      ->orderBy('start_date');

    return $this->getFilteredResults(
      $query,
      $request,
      ScrumSprint::filters,
      ScrumSprint::sorts,
      ScrumSprintResource::class,
    );
  }

  public function show(int $id): ScrumSprint
  {
    $key = "scrum:sprint:{$id}";
    return Cache::store('redis')->remember($key, self::CACHE_TTL, function () use ($id) {
      return ScrumSprint::with(['project:id,name,color'])
        ->withCount(['items', 'items as done_count' => fn($q) => $q->where('status', 'hecho')])
        ->findOrFail($id);
    });
  }

  public function find(int $id) { return $this->show($id); }

  public function store(mixed $data): ScrumSprint
  {
    $sprint = ScrumSprint::create($data);
    $this->flushSprintCache($sprint->project_id);
    return $sprint;
  }

  public function update(mixed $data): ScrumSprint
  {
    $sprint = ScrumSprint::findOrFail($data['id']);
    $sprint->update($data);
    $this->flushSprintCache($sprint->project_id, $sprint->id);
    return $sprint->fresh();
  }

  public function destroy(int $id): void
  {
    $sprint = ScrumSprint::findOrFail($id);
    $projectId = $sprint->project_id;
    $sprint->delete();
    $this->flushSprintCache($projectId, $id);
  }

  public function activate(int $id): ScrumSprint
  {
    $sprint = ScrumSprint::findOrFail($id);
    ScrumSprint::where('project_id', $sprint->project_id)->where('status', 'activo')->update(['status' => 'cerrado']);
    $sprint->update(['status' => 'activo']);
    $this->flushSprintCache($sprint->project_id, $id);
    return $sprint->fresh();
  }

  public function close(int $id): ScrumSprint
  {
    $sprint = ScrumSprint::findOrFail($id);
    $sprint->update(['status' => 'cerrado']);
    $this->flushSprintCache($sprint->project_id, $id);
    return $sprint->fresh();
  }

  private function flushSprintCache(int $projectId, ?int $sprintId = null): void
  {
    Cache::store('redis')->forget("scrum:sprints:project:{$projectId}:*");
    if ($sprintId) {
      Cache::store('redis')->forget("scrum:sprint:{$sprintId}");
      Cache::store('redis')->forget("scrum:kanban:{$sprintId}");
    }
  }
}
