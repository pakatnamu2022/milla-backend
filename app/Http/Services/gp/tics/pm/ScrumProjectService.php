<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Resources\gp\tics\pm\ScrumProjectResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ScrumProjectService extends BaseService implements BaseServiceInterface
{
  private const CACHE_TTL = 1800; // 30 min

  public function list(Request $request)
  {
    $query = ScrumProject::query()
      ->with(['creator:id,name', 'activeSprint'])
      ->withCount(['sprints', 'items']);

    return $this->getFilteredResults(
      $query,
      $request,
      ScrumProject::filters,
      ScrumProject::sorts,
      ScrumProjectResource::class,
    );
  }

  public function find(int $id)
  {
    return $this->show($id);
  }

  public function show(int $id): ScrumProject
  {
    $key = "scrum:project:{$id}";
    return Cache::store('redis')->remember($key, self::CACHE_TTL, function () use ($id) {
      return ScrumProject::with(['creator:id,name', 'sprints', 'tags'])
        ->withCount('items')
        ->findOrFail($id);
    });
  }

  public function store(mixed $data): ScrumProject
  {
    $data['created_by'] = Auth::id();
    $project = ScrumProject::create($data);
    $this->flushProjectsCache();
    return $project->load('creator:id,name');
  }

  public function update(mixed $data): ScrumProject
  {
    $project = ScrumProject::findOrFail($data['id']);
    $project->update($data);
    $this->flushProjectCache($project->id);
    return $project->fresh();
  }

  public function destroy(int $id): void
  {
    ScrumProject::findOrFail($id)->delete();
    $this->flushProjectCache($id);
  }

  private function flushProjectsCache(): void
  {
    Cache::store('redis')->flush();
  }

  public function flushProjectCache(int $projectId): void
  {
    Cache::store('redis')->forget("scrum:project:{$projectId}");
    Cache::store('redis')->forget("scrum:sprints:project:{$projectId}");
    Cache::store('redis')->forget("scrum:backlog:{$projectId}");
  }
}
