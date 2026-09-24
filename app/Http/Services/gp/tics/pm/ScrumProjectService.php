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
   * PDF descargable del Gantt del proyecto, al estilo MS Project: una sola
   * tabla con numeración WBS jerárquica (sprint 1, historia 1.1, tarea
   * 1.1.1), columnas de fechas/duración/estado/predecesora, y una línea de
   * tiempo continua a la derecha (barras posicionadas por porcentaje sobre
   * el rango completo del proyecto, no una cuadrícula de días repetida por
   * sprint) para que todo el proyecto se vea en una sola línea compacta.
   */
  public function ganttPdf(int $projectId)
  {
    $data = $this->ganttPdfViewData($projectId);
    $filename = 'gantt-' . Str::slug($data['project']['name']) . '-' . now()->format('Ymd-His') . '.pdf';

    $pdf = Pdf::loadView('exports.scrum-project-gantt', $data)->setPaper('a3', 'landscape');
    $dompdf = $pdf->getDomPDF();
    $dompdf->render();
    // La página A3 horizontal (420mm) es más ancha que cualquier pantalla,
    // así que al abrir el PDF a zoom 100% el visor solo mostraba las
    // columnas de texto de la izquierda y la línea de tiempo quedaba fuera
    // de vista (parecía "columna super ancha" sin querer scrollear).
    // FitH fuerza que el visor abra encajando el ancho completo de la
    // página, mostrando tabla + timeline juntos desde el primer vistazo.
    $dompdf->getCanvas()->set_default_view('FitH', [0]);

    // El endpoint del frontend siempre pega a la misma URL (sin query
    // param), así que sin esto el navegador podía servir una descarga
    // vieja cacheada en vez de regenerar el PDF con los datos actuales.
    return response($dompdf->output(), 200)
      ->header('Content-Type', 'application/pdf')
      ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
      ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
  }

  /**
   * HTML crudo (sin pasar por dompdf) de la misma vista del Gantt, para
   * poder inspeccionar el layout en un navegador normal mientras se ajusta
   * el diseño — dompdf tiene soporte limitado de CSS y a veces renderiza
   * distinto a un navegador real, así que conviene validar el HTML primero.
   */
  public function ganttPdfHtml(int $projectId): string
  {
    return view('exports.scrum-project-gantt', $this->ganttPdfViewData($projectId))->render();
  }

  private function ganttPdfViewData(int $projectId): array
  {
    $gantt = $this->gantt($projectId);

    // El rango de sprints puede quedar corto si las fechas propias de un
    // item (por cascadeDueDateShift) se salen de su sprint: se recalcula el
    // rango real a partir de todas las fechas de items para que ninguna
    // barra quede fuera de la línea de tiempo.
    $allItemDates = collect($gantt['sprints'])
      ->flatMap(fn (array $sprint) => collect($sprint['items'])->flatMap(fn (array $item) => [$item['start_at'], $item['end_at']]))
      ->filter()
      ->map(fn ($date) => Carbon::parse($date));

    $rangeStart = $allItemDates->min() ?? Carbon::parse($gantt['range']['start'] ?? now());
    $rangeEnd = $allItemDates->max() ?? Carbon::parse($gantt['range']['end'] ?? $rangeStart);
    $totalDays = max(1, $rangeStart->diffInDays($rangeEnd) + 1);

    // Ancho real (en mm) de la columna de línea de tiempo en el PDF (ver
    // .col-timeline en la vista). Se posicionan las barras en mm en vez de
    // en % porque dompdf no resuelve bien los % de position:absolute
    // cuando dependen del ancho calculado de una celda de tabla.
    $timelineWidthMm = 296;
    $mm = function (Carbon $date) use ($rangeStart, $totalDays, $timelineWidthMm) {
      return max(0, min($timelineWidthMm, ($rangeStart->diffInDays($date) / $totalDays) * $timelineWidthMm));
    };

    // Cabecera año/mes de la línea de tiempo, recortada al rango real.
    $months = [];
    $cursor = $rangeStart->copy()->startOfMonth();
    while ($cursor->lte($rangeEnd)) {
      $monthStart = $cursor->copy()->max($rangeStart);
      $monthEnd = $cursor->copy()->endOfMonth()->min($rangeEnd);
      $months[] = [
        'year' => $cursor->year,
        'label' => Str::ucfirst($cursor->translatedFormat('M')),
        'left' => $mm($monthStart),
        'width' => max(1, $mm($monthEnd->copy()->addDay()) - $mm($monthStart)),
      ];
      $cursor->addMonthNoOverflow();
    }
    $years = collect($months)
      ->groupBy('year')
      ->map(function ($group, $year) {
        $left = $group->min('left');
        return ['year' => $year, 'left' => $left, 'width' => $group->sum('width')];
      })
      ->values();

    $todayMm = now()->between($rangeStart, $rangeEnd) ? $mm(now()) : null;

    $statusLabels = [
      'backlog' => 'Backlog',
      'por_hacer' => 'Por hacer',
      'en_progreso' => 'En progreso',
      'en_revision' => 'En revisión',
      'hecho' => 'Hecho',
    ];
    $sprintStatusLabels = ['planeado' => 'Planeado', 'activo' => 'Activo', 'cerrado' => 'Cerrado'];

    // Arma la lista plana de filas (sprint, historia, tarea) con numeración
    // WBS jerárquica y predecesora calculada igual que las flechas del
    // Gantt en pantalla: por tipo, dentro del mismo sprint (una historia
    // encadena con la historia anterior, una tarea con la tarea anterior),
    // sin cruzar entre historias y tareas.
    $rows = [];
    $lastSprintWbs = null;
    foreach ($gantt['sprints'] as $sprintIndex => $sprint) {
      $sprintWbs = (string) ($sprintIndex + 1);
      $sprintStart = $sprint['start_date'] ? Carbon::parse($sprint['start_date']) : null;
      $sprintEnd = $sprint['end_date'] ? Carbon::parse($sprint['end_date']) : null;

      $rows[] = [
        'wbs' => $sprintWbs,
        'level' => 0,
        'type' => 'sprint',
        'title' => $sprint['name'],
        'start' => $sprintStart,
        'end' => $sprintEnd,
        'status_label' => $sprintStatusLabels[$sprint['status']] ?? ucfirst($sprint['status']),
        'predecessor' => $lastSprintWbs,
        'left' => $sprintStart ? $mm($sprintStart) : 0,
        'width' => ($sprintStart && $sprintEnd) ? max(1, $mm($sprintEnd->copy()->addDay()) - $mm($sprintStart)) : 0,
      ];
      $lastSprintWbs = $sprintWbs;

      $groupIndex = 0;
      $taskIndexInGroup = 0;
      $currentHistoriaWbs = null;
      $lastByType = ['historia' => null, 'tarea' => null];
      $lastStartByType = ['historia' => null, 'tarea' => null];

      foreach ($sprint['items'] as $item) {
        $itemStart = Carbon::parse($item['start_at']);
        $itemEnd = Carbon::parse($item['end_at']);

        if ($item['type'] === 'historia') {
          $groupIndex++;
          $taskIndexInGroup = 0;
          $wbs = "{$sprintWbs}.{$groupIndex}";
          $currentHistoriaWbs = $wbs;
          $level = 1;
        } else {
          $taskIndexInGroup++;
          $wbs = $currentHistoriaWbs !== null
            ? "{$currentHistoriaWbs}.{$taskIndexInGroup}"
            : "{$sprintWbs}." . ($groupIndex + 1);
          $level = 2;
        }

        $type = $item['type'];
        $prevStart = $lastStartByType[$type];
        $predecessor = ($prevStart !== null && !$prevStart->isSameDay($itemStart)) ? $lastByType[$type] : null;
        $lastByType[$type] = $wbs;
        $lastStartByType[$type] = $itemStart;

        $rows[] = [
          'wbs' => $wbs,
          'level' => $level,
          'type' => $item['type'],
          'title' => $item['title'],
          'start' => $itemStart,
          'end' => $itemEnd,
          'status_label' => $statusLabels[$item['status']] ?? $item['status'],
          'status' => $item['status'],
          'predecessor' => $predecessor,
          'left' => $mm($itemStart),
          'width' => max(1, $mm($itemEnd->copy()->addDay()) - $mm($itemStart)),
        ];
      }
    }

    return [
      'project' => $gantt['project'],
      'range' => ['start' => $rangeStart->format('Y-m-d'), 'end' => $rangeEnd->format('Y-m-d')],
      'years' => $years,
      'months' => $months,
      'todayMm' => $todayMm,
      'rows' => $rows,
      'generatedAt' => $gantt['generated_at'],
    ];
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
