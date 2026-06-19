<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScrumProjectService extends BaseService implements BaseServiceInterface
{
  private const CACHE_TTL = 1800; // 30 min

  public function list(Request $request)
  {
    $key = 'scrum:projects:' . md5(serialize($request->all()));
    return Cache::store('redis')->remember($key, self::CACHE_TTL, function () use ($request) {
      return ScrumProject::filter($request)
        ->with(['creator:id,name', 'activeSprint'])
        ->withCount(['sprints', 'items'])
        ->paginate($request->get('per_page', 15));
    });
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
