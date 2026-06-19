<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Resources\gp\tics\pm\ScrumTagResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScrumTagService extends BaseService implements BaseServiceInterface
{
  private const CACHE_TTL = 3600; // 1h — los tags cambian poco

  public function list(Request $request)
  {
    $query = ScrumTag::query()->orderBy('name');

    return $this->getFilteredResults(
      $query,
      $request,
      ScrumTag::filters,
      ScrumTag::sorts,
      ScrumTagResource::class,
    );
  }

  public function find(int $id) { return null; }
  public function show(int $id) { return null; }

  public function store(mixed $data): ScrumTag
  {
    $tag = ScrumTag::create($data);
    Cache::store('redis')->forget("scrum:tags:project:{$tag->project_id}");
    return $tag;
  }

  public function update(mixed $data): ScrumTag
  {
    $tag = ScrumTag::findOrFail($data['id']);
    $tag->update($data);
    Cache::store('redis')->forget("scrum:tags:project:{$tag->project_id}");
    return $tag->fresh();
  }

  public function destroy(int $id): void
  {
    $tag = ScrumTag::findOrFail($id);
    Cache::store('redis')->forget("scrum:tags:project:{$tag->project_id}");
    $tag->delete();
  }
}
