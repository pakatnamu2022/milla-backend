<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Resources\gp\tics\pm\ScrumItemHistoryResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumItemHistory;
use Illuminate\Http\Request;

class ScrumItemHistoryService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    $query = ScrumItemHistory::query()->with('user:id,name')->orderByDesc('created_at');

    return $this->getFilteredResults(
      $query,
      $request,
      ScrumItemHistory::filters,
      ScrumItemHistory::sorts,
      ScrumItemHistoryResource::class,
    );
  }

  public function find(int $id) { return null; }
  public function show(int $id) { return null; }
  public function store(mixed $data) { return null; }
  public function update(mixed $data) { return null; }
  public function destroy(int $id): void { }
}
