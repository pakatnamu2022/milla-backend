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

  /**
   * Tablero Kanban filtrable. Trae tanto historias como sus tareas hijas
   * (Análisis, Desarrollo, Pruebas...): cada una es su propia tarjeta, en la
   * columna de su propio status, para que se puedan mover independientemente.
   * Ya no requiere sprint: por defecto muestra todo el proyecto, sin importar
   * en qué sprint (Desarrollo/Pruebas) caiga cada tarea del item.
   */
  public function kanban(array $filters = []): array
  {
    $query = ScrumItem::query()
      ->with(['assignee:id,name', 'tags', 'parent:id,title'])
      ->orderBy('order');

    if (!empty($filters['project_id'])) {
      $query->where('project_id', $filters['project_id']);
    }
    if (!empty($filters['sprint_id'])) {
      $query->where('sprint_id', $filters['sprint_id']);
    }
    if (!empty($filters['assigned_to'])) {
      $query->where('assigned_to', $filters['assigned_to']);
    }
    if (!empty($filters['priority'])) {
      $query->where('priority', $filters['priority']);
    }
    if (!empty($filters['tag_id'])) {
      $query->whereHas('tags', fn ($q) => $q->where('scrum_tags.id', $filters['tag_id']));
    }
    if (!empty($filters['history_id'])) {
      $query->where(fn ($q) => $q->where('id', $filters['history_id'])->orWhere('parent_id', $filters['history_id']));
    }

    return $query->get()->groupBy('status')->toArray();
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
      'predecessor:id,title,due_date',
      'successors:id,title,predecessor_id,start_date,due_date',
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

    $previousDueDate = $item->due_date;

    $item->update($data);

    if (!empty($histories)) {
      ScrumItemHistory::insert($histories);
    }

    if (array_key_exists('tag_ids', $data)) {
      $item->tags()->sync($data['tag_ids']);
    }

    if (array_key_exists('due_date', $data)) {
      $this->cascadeDueDateShift($item, $previousDueDate, $item->due_date);
    }

    $this->flushItemCache($item);
    return $item->fresh()->load(['assignee:id,name', 'tags']);
  }

  /**
   * Cuando cambia la fecha fin de un item, sus sucesores (items cuyo
   * predecessor_id apunta a este) se desplazan la misma cantidad de días,
   * en cascada, para respetar la duración/holgura de cada uno.
   */
  private function cascadeDueDateShift(ScrumItem $item, ?\Illuminate\Support\Carbon $previousDueDate, ?\Illuminate\Support\Carbon $newDueDate, array $visited = []): void
  {
    if (!$previousDueDate || !$newDueDate || in_array($item->id, $visited)) {
      return;
    }

    $deltaDays = $previousDueDate->diffInDays($newDueDate, false);
    if ($deltaDays === 0) {
      return;
    }

    $visited[] = $item->id;

    $successors = ScrumItem::where('predecessor_id', $item->id)->get();
    foreach ($successors as $successor) {
      $successorPreviousDue = $successor->due_date;

      $successor->update([
        'start_date' => $successor->start_date?->addDays($deltaDays),
        'due_date'   => $successor->due_date?->addDays($deltaDays),
      ]);

      $this->cascadeDueDateShift($successor, $successorPreviousDue, $successor->due_date, $visited);
    }
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
    Cache::store('redis')->forget("scrum:backlog:{$item->project_id}");
  }

  private function flushKanbanAndBacklog(?int $sprintId, int $projectId): void
  {
    Cache::store('redis')->forget("scrum:backlog:{$projectId}");
  }
}
