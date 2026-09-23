<?php

namespace App\Http\Services\gp\tics\pm;

use App\Http\Resources\gp\tics\pm\ScrumProjectResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\pm\ScrumItem;
use App\Models\gp\tics\pm\ScrumProject;
use App\Models\gp\tics\pm\ScrumSprint;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

  /**
   * Estructura de Gantt calculada en vivo (sin caché) a partir de los sprints
   * e items actuales del proyecto: cada vez que se registra o modifica un
   * item, esta respuesta refleja el estado real.
   */
  public function gantt(int $projectId): array
  {
    $project = ScrumProject::findOrFail($projectId);

    $sprints = ScrumSprint::where('project_id', $projectId)
      ->orderBy('start_date')
      ->get();

    $items = ScrumItem::where('project_id', $projectId)
      ->with(['assignee:id,name'])
      ->orderBy('order')
      ->get();

    $itemsBySprint = $items->groupBy('sprint_id');
    $itemsById = $items->keyBy('id');
    $childrenByParent = $items->whereNotNull('parent_id')->groupBy('parent_id');

    // start_at NO es la fecha de inicio del sprint para todos los items: cada
    // item empieza justo donde termina el anterior en su misma cadena (ver
    // $chainStartByItemId más abajo), para que el Gantt se vea en escalera
    // real (uno empieza cuando el otro termina) en vez de todo apilado desde
    // el primer día del sprint.
    $mapItem = function (ScrumItem $item, ?Carbon $chainStart) use ($sprints, $childrenByParent, $itemsById) {
      $sprint = $item->sprint_id ? $sprints->firstWhere('id', $item->sprint_id) : null;
      // Preferimos la start_date propia del item (calculada por el seeder o
      // por cascadeDueDateShift) sobre la cadena visual del sprint: ahora que
      // Desarrollo y Pruebas de una historia comparten sprint, encadenar TODO
      // el sprint secuencialmente pondría el desarrollo de la siguiente
      // historia recién después de las pruebas de la anterior, perdiendo el
      // paralelismo real entre historias.
      $startAt = $item->start_date?->format('Y-m-d') ?? $chainStart?->format('Y-m-d') ?? $sprint?->start_date?->format('Y-m-d') ?? $item->created_at->format('Y-m-d');
      $endAt = $item->due_date?->format('Y-m-d') ?? $sprint?->end_date?->format('Y-m-d') ?? $startAt;

      $children = $childrenByParent[$item->id] ?? collect();
      $progress = $children->isNotEmpty()
        ? round(($children->where('status', 'hecho')->count() / $children->count()) * 100, 1)
        : ($item->status === 'hecho' ? 100 : 0);

      $parent = $item->parent_id ? $itemsById->get($item->parent_id) : null;

      return [
        'id' => $item->id,
        'parent_id' => $item->parent_id,
        'parent_title' => $parent?->title,
        'parent_in_same_sprint' => $parent ? $parent->sprint_id === $item->sprint_id : null,
        'title' => $item->title,
        'type' => $item->type,
        'status' => $item->status,
        'priority' => $item->priority,
        'assignee' => $item->assignee?->name,
        'start_at' => $startAt,
        'end_at' => $endAt,
        'progress' => $progress,
      ];
    };

    // Orden de la cadena dentro de un sprint: por el "grupo" (la historia
    // dueña, o el propio item si no tiene padre/no está en este sprint), y
    // dentro del grupo la historia va antes que sus tareas.
    $chainSort = function ($item) use ($itemsById) {
      $parent = $item->parent_id ? $itemsById->get($item->parent_id) : null;
      $groupOrder = $parent->order ?? $item->order;
      $rank = $item->type === 'historia' ? -1 : $item->order;
      return [$groupOrder, $rank];
    };

    $mapChained = function ($sprintItems, ?Carbon $chainStart) use ($chainSort, $mapItem) {
      $sorted = $sprintItems->sort(function ($a, $b) use ($chainSort) {
        return $chainSort($a) <=> $chainSort($b);
      })->values();

      $cursor = $chainStart;
      return $sorted->map(function (ScrumItem $item) use (&$cursor, $mapItem) {
        $mapped = $mapItem($item, $cursor);
        if ($item->type !== 'historia' && $item->due_date) {
          $cursor = $item->due_date;
        }
        return $mapped;
      })->values();
    };

    $sprintNodes = $sprints->map(function (ScrumSprint $sprint) use ($itemsBySprint, $mapChained) {
      $sprintItems = $itemsBySprint[$sprint->id] ?? collect();
      return [
        'id' => $sprint->id,
        'name' => $sprint->name,
        'kind' => str_contains(mb_strtolower($sprint->name), 'prueba') ? 'test' : 'dev',
        'start_date' => $sprint->start_date?->format('Y-m-d'),
        'end_date' => $sprint->end_date?->format('Y-m-d'),
        'status' => $sprint->status,
        'items' => $mapChained($sprintItems, $sprint->start_date),
      ];
    })->values();

    $backlogItems = $itemsBySprint[null] ?? collect();

    $allDates = $sprints->flatMap(fn($s) => [$s->start_date, $s->end_date])->filter();

    return [
      'project' => [
        'id' => $project->id,
        'name' => $project->name,
        'color' => $project->color,
        'status' => $project->status,
      ],
      'range' => [
        'start' => $allDates->min()?->format('Y-m-d'),
        'end' => $allDates->max()?->format('Y-m-d'),
      ],
      'sprints' => $sprintNodes,
      'backlog' => $backlogItems->sortBy('order')->map(fn ($item) => $mapItem($item, null))->values(),
      'generated_at' => now()->toIso8601String(),
    ];
  }

  /**
   * PDF descargable del Gantt del proyecto: reutiliza gantt() y precalcula,
   * por cada sprint, la cuadrícula de días y el offset/ancho de cada item
   * dentro de esa cuadrícula, para poder dibujarlo como una tabla en el PDF
   * (dompdf no soporta flex/grid).
   */
  public function ganttPdf(int $projectId)
  {
    $gantt = $this->gantt($projectId);

    $sprintRows = collect($gantt['sprints'])->map(function (array $sprint) {
      $start = Carbon::parse($sprint['start_date']);
      $end = Carbon::parse($sprint['end_date']);
      $totalDays = max(1, $start->diffInDays($end) + 1);

      $days = [];
      for ($d = 0; $d < $totalDays; $d++) {
        $days[] = $start->copy()->addDays($d);
      }

      $items = collect($sprint['items'])->map(function (array $item) use ($start, $totalDays) {
        $itemStart = Carbon::parse($item['start_at']);
        $itemEnd = Carbon::parse($item['end_at']);
        $offset = max(0, min($totalDays - 1, $start->diffInDays($itemStart)));
        $span = max(1, $itemStart->diffInDays($itemEnd) + 1);
        $span = max(1, min($span, $totalDays - $offset));

        return array_merge($item, ['offset' => $offset, 'span' => $span]);
      })->values();

      return [
        'name' => $sprint['name'],
        'kind' => $sprint['kind'],
        'status' => $sprint['status'],
        'start_date' => $sprint['start_date'],
        'end_date' => $sprint['end_date'],
        'days' => $days,
        'items' => $items,
      ];
    })->values();

    $filename = 'gantt-' . Str::slug($gantt['project']['name']) . '-' . now()->format('Ymd-His') . '.pdf';

    return Pdf::loadView('exports.scrum-project-gantt', [
      'project' => $gantt['project'],
      'range' => $gantt['range'],
      'sprints' => $sprintRows,
      'generatedAt' => $gantt['generated_at'],
    ])
      ->setPaper('a3', 'landscape')
      ->stream($filename);
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
