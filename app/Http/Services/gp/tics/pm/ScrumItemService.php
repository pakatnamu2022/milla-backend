<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Resources\gp\tics\pm\ScrumItemResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumItem;
use App\Models\gp\tics\pm\ScrumItemHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ScrumItemService extends BaseService implements BaseServiceInterface
{
  private const KANBAN_TTL = 300;  // 5 min — cambia frecuente
  private const BACKLOG_TTL = 600; // 10 min

  public function list(Request $request)
  {
    $query = ScrumItem::query()
      ->with(['assignee:id,name', 'tags', 'creator:id,name'])
      ->withCount('children');

    return $this->getFilteredResults(
      $query,
      $request,
      ScrumItem::filters,
      ScrumItem::sorts,
      ScrumItemResource::class,
    );
  }

  public function kanban(?int $sprintId = null): array
  {
    $key = $sprintId ? "scrum:kanban:{$sprintId}" : "scrum:kanban:all";
    return Cache::store('redis')->remember($key, self::KANBAN_TTL, function () use ($sprintId) {
      $query = ScrumItem::query()
        ->whereNull('parent_id')
        ->with(['assignee:id,name', 'tags', 'children:id,parent_id,title,status,order'])
        ->orderBy('order');

      if ($sprintId !== null) {
        $query->where('sprint_id', $sprintId);
      }

      return $query->get()->groupBy('status')->toArray();
    });
  }

  public function backlog(int $projectId): array
  {
    $key = "scrum:backlog:{$projectId}";
    return Cache::store('redis')->remember($key, self::BACKLOG_TTL, function () use ($projectId) {
      return ScrumItem::where('project_id', $projectId)
        ->whereNull('sprint_id')
        ->whereNull('parent_id')
        ->with(['assignee:id,name', 'tags'])
        ->orderBy('priority')
        ->orderBy('order')
        ->get()
        ->toArray();
    });
  }

  public function show(int $id): ScrumItem
  {
    return ScrumItem::with([
      'assignee:id,name',
      'creator:id,name',
      'sprint:id,name,status',
      'project:id,name,color',
      'parent:id,title',
      'children:id,parent_id,title,status,priority,assigned_to,order',
      'tags',
      'watchers:id,name',
      'comments.user:id,name',
      'history.user:id,name',
    ])->findOrFail($id);
  }

  public function find(int $id) { return $this->show($id); }

  public function store(mixed $data): ScrumItem
  {
    $data['created_by'] = Auth::id();
    $data['order'] = $this->nextOrder($data['sprint_id'] ?? null, $data['project_id']);

    $item = ScrumItem::create($data);

    if (!empty($data['tag_ids'])) {
      $item->tags()->sync($data['tag_ids']);
    }

    $this->flushItemCache($item);
    return $item->load(['assignee:id,name', 'tags']);
  }

  public function update(mixed $data): ScrumItem
  {
    $item = ScrumItem::findOrFail($data['id']);
    $trackedFields = ['status', 'priority', 'sprint_id', 'assigned_to', 'story_points', 'estimated_hours'];

    $histories = [];
    foreach ($trackedFields as $field) {
      if (array_key_exists($field, $data) && (string)$item->$field !== (string)$data[$field]) {
        $histories[] = [
          'item_id'    => $item->id,
          'user_id'    => Auth::id(),
          'field'      => $field,
          'old_value'  => $item->$field,
          'new_value'  => $data[$field],
          'created_at' => now(),
        ];
      }
    }

    if (array_key_exists('status', $data) && $data['status'] === 'hecho' && $item->status !== 'hecho') {
      $data['closed_at'] = now();
    } elseif (array_key_exists('status', $data) && $data['status'] !== 'hecho') {
      $data['closed_at'] = null;
    }

    $item->update($data);

    if (!empty($histories)) {
      ScrumItemHistory::insert($histories);
    }

    if (array_key_exists('tag_ids', $data)) {
      $item->tags()->sync($data['tag_ids']);
    }

    $this->flushItemCache($item);
    return $item->fresh()->load(['assignee:id,name', 'tags']);
  }

  public function submitTicket(mixed $data): ScrumItem
  {
    $data['type']       = 'solicitud';
    $data['status']     = 'backlog';
    $data['sprint_id']  = null;
    $data['created_by'] = Auth::id();
    $data['order']      = $this->nextOrder(null, $data['project_id']);

    $item = ScrumItem::create($data);
    Cache::store('redis')->forget("scrum:backlog:{$data['project_id']}");
    return $item->load(['creator:id,name']);
  }

  public function reorder(array $items, ?int $sprintId, int $projectId): void
  {
    foreach ($items as $order => $id) {
      ScrumItem::where('id', $id)->update(['order' => $order]);
    }
    $this->flushKanbanAndBacklog($sprintId, $projectId);
  }

  public function destroy(int $id): void
  {
    $item = ScrumItem::findOrFail($id);
    $this->flushItemCache($item);
    $item->delete();
  }

  public function toggleWatcher(int $itemId): bool
  {
    $item = ScrumItem::findOrFail($itemId);
    $userId = Auth::id();
    $watching = $item->watchers()->where('user_id', $userId)->exists();
    $watching ? $item->watchers()->detach($userId) : $item->watchers()->attach($userId);
    return !$watching;
  }

  private function nextOrder(?int $sprintId, int $projectId): int
  {
    $query = ScrumItem::where('project_id', $projectId);
    $sprintId ? $query->where('sprint_id', $sprintId) : $query->whereNull('sprint_id');
    return $query->max('order') + 1;
  }

  private function flushItemCache(ScrumItem $item): void
  {
    Cache::store('redis')->forget("scrum:kanban:{$item->sprint_id}");
    Cache::store('redis')->forget("scrum:backlog:{$item->project_id}");
  }

  private function flushKanbanAndBacklog(?int $sprintId, int $projectId): void
  {
    if ($sprintId) Cache::store('redis')->forget("scrum:kanban:{$sprintId}");
    Cache::store('redis')->forget("scrum:backlog:{$projectId}");
  }
}
