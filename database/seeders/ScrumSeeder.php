<?php

namespace Database\Seeders;

use App\Http\Utils\Constants;
use App\Models\gp\tics\pm\ScrumItem;
use App\Models\gp\tics\pm\ScrumProject;
use App\Models\gp\tics\pm\ScrumSprint;
use App\Models\gp\tics\pm\ScrumTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScrumSeeder extends Seeder
{
  private const PROJECTS = [
    ['name' => 'Portal Académico', 'color' => '#3B82F6', 'description' => 'Rediseño del portal de gestión académica para docentes y estudiantes.'],
    ['name' => 'App de Viáticos', 'color' => '#10B981', 'description' => 'Módulo de solicitud, aprobación y rendición de viáticos del personal.'],
    ['name' => 'Sistema de Compras', 'color' => '#F59E0B', 'description' => 'Automatización del proceso de adquisiciones y órdenes de compra.'],
  ];

  private const TAGS = [
    ['name' => 'Backend', 'color' => 'orange'],
    ['name' => 'Frontend', 'color' => 'blue'],
    ['name' => 'Urgente', 'color' => 'red'],
    ['name' => 'Bloqueado', 'color' => 'gray'],
    ['name' => 'QA', 'color' => 'emerald'],
    ['name' => 'Diseño', 'color' => 'amber'],
    ['name' => 'Refactor', 'color' => 'indigo'],
  ];

  private const SPRINT_GOALS = [
    'Completar el módulo de autenticación y gestión de usuarios.',
    'Implementar el CRUD de entidades principales y sus validaciones.',
    'Integrar notificaciones por correo y en tiempo real.',
    'Optimizar consultas críticas y mejorar tiempos de respuesta.',
    'Finalizar pantallas pendientes y ajustes de UX según feedback.',
  ];

  private const HISTORIAS = [
    'Como usuario quiero iniciar sesión con mis credenciales institucionales.',
    'Como administrador quiero gestionar roles y permisos del sistema.',
    'Como usuario quiero recibir notificaciones cuando cambie el estado de mi solicitud.',
    'Como jefe de área quiero aprobar o rechazar solicitudes desde el panel.',
    'Como usuario quiero exportar los reportes en formato Excel y PDF.',
    'Como desarrollador quiero documentar todos los endpoints en Swagger.',
    'Como QA quiero ejecutar pruebas automatizadas en el pipeline de CI.',
    'Como usuario quiero ver el historial de cambios de cada registro.',
  ];

  private const TAREAS = [
    'Crear migración y modelo de la entidad.',
    'Implementar controller con validaciones.',
    'Agregar resource y filtros al listado.',
    'Diseñar componente de formulario en Vue.',
    'Escribir pruebas unitarias del servicio.',
    'Revisar y refactorizar el servicio existente.',
    'Configurar caché en Redis para el endpoint.',
    'Integrar con el módulo de notificaciones.',
    'Ajustar estilos según guía de diseño.',
    'Documentar endpoint en la colección de Postman.',
  ];

  private const ERRORES = [
    'El filtro de fechas no respeta el timezone del servidor.',
    'Error 500 al intentar exportar con más de 1000 registros.',
    'El modal de confirmación no cierra tras acción exitosa.',
    'La paginación regresa resultados duplicados en la segunda página.',
    'El correo de notificación llega sin formato al cliente de Gmail.',
  ];

  private const FUNCIONES = [
    'Implementar búsqueda global con Meilisearch.',
    'Agregar modo oscuro al panel administrativo.',
    'Integrar firma digital en documentos de aprobación.',
    'Agregar filtros avanzados con múltiples criterios.',
    'Implementar importación masiva desde Excel.',
  ];

  public function run(): void
  {
    $ticsUserIds = DB::table('config_asig_role_user')
      ->where('role_id', Constants::TICS_ROL_ID)
      ->where('status_deleted', 1)
      ->pluck('user_id');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('scrum_item_watcher')->truncate();
    DB::table('scrum_item_tag')->truncate();
    DB::table('scrum_item_history')->truncate();
    DB::table('scrum_comments')->truncate();
    DB::table('scrum_items')->truncate();
    DB::table('scrum_tags')->truncate();
    DB::table('scrum_sprints')->truncate();
    DB::table('scrum_projects')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

//    return;

    foreach (self::PROJECTS as $projectData) {
      $project = ScrumProject::factory()->activo()->create([
        ...$projectData,
        'created_by' => $ticsUserIds->random(),
      ]);

      $tags = collect(self::TAGS)->map(
        fn($tag) => ScrumTag::create([...$tag, 'project_id' => $project->id])
      );

      $sprintCerrado = ScrumSprint::factory()->cerrado()->create([
        'project_id' => $project->id,
        'name'       => 'Sprint 1',
        'goal'       => self::SPRINT_GOALS[0],
        'start_date' => now()->subDays(42)->format('Y-m-d'),
        'end_date'   => now()->subDays(28)->format('Y-m-d'),
//        'created_by' => $ticsUserIds->random(),
      ]);

      $sprintActivo = ScrumSprint::factory()->activo()->create([
        'project_id' => $project->id,
        'name'       => 'Sprint 2',
        'goal'       => self::SPRINT_GOALS[1],
        'start_date' => now()->subDays(14)->format('Y-m-d'),
        'end_date'   => now()->addDays(0)->format('Y-m-d'),
//        'created_by' => $ticsUserIds->random(),
      ]);

      $sprintPlan = ScrumSprint::factory()->create([
        'project_id' => $project->id,
        'name'       => 'Sprint 3',
        'goal'       => self::SPRINT_GOALS[2],
        'status'     => 'planeado',
        'start_date' => now()->addDays(1)->format('Y-m-d'),
        'end_date'   => now()->addDays(14)->format('Y-m-d'),
//        'created_by' => $ticsUserIds->random(),
      ]);

      $this->crearItems($project->id, $sprintCerrado->id, $tags, 'hecho', 6, $ticsUserIds);
      $this->crearItems($project->id, $sprintActivo->id, $tags, 'mix', 10, $ticsUserIds);
      $this->crearItems($project->id, $sprintPlan->id, $tags, 'por_hacer', 6, $ticsUserIds);
      $this->crearItems($project->id, null, $tags, 'backlog', 5, $ticsUserIds);

      $historias = ScrumItem::where('project_id', $project->id)
        ->where('sprint_id', $sprintActivo->id)
        ->where('type', 'historia')
        ->take(3)
        ->get();

      foreach ($historias as $i => $historia) {
        $subtareas = array_slice(self::TAREAS, $i * 3, 3);
        foreach ($subtareas as $order => $titulo) {
          ScrumItem::create([
            'project_id'   => $project->id,
            'sprint_id'    => $historia->sprint_id,
            'parent_id'    => $historia->id,
            'type'         => 'tarea',
            'title'        => $titulo,
            'status'       => collect(['por_hacer', 'en_progreso', 'hecho'])->random(),
            'priority'     => collect(['Alta', 'Media', 'Baja'])->random(),
            'story_points' => collect([1, 2, 3])->random(),
            'order'        => $order,
            'created_by'   => $ticsUserIds->random(),
            'assigned_to'  => $ticsUserIds->random(),
          ]);
        }
      }
    }
  }

  private function crearItems(int $projectId, ?int $sprintId, $tags, string $modo, int $cantidad, Collection $ticsUserIds): void
  {
    $pool = $this->poolTitulos($modo);
    $titles = collect($pool)->shuffle()->take($cantidad);

    foreach ($titles as $order => $titulo) {
      [$type, $title] = $titulo;
      $status = $this->resolveStatus($modo);

      $item = ScrumItem::factory()->create([
        'project_id'  => $projectId,
        'sprint_id'   => $sprintId,
        'type'        => $type,
        'title'       => $title,
        'status'      => $status,
        'order'       => $order,
        'created_by'  => $ticsUserIds->random(),
        'assigned_to' => $ticsUserIds->random(),
        'closed_at'   => $status === 'hecho' ? now()->subDays(rand(1, 10)) : null,
      ]);

      $tagCount = min(rand(0, 2), $tags->count());
      if ($tagCount > 0) {
        $item->tags()->attach($tags->random($tagCount)->pluck('id'));
      }
    }
  }

  private function poolTitulos(string $modo): array
  {
    $historias = collect(self::HISTORIAS)->map(fn($t) => ['historia', $t])->all();
    $tareas = collect(self::TAREAS)->map(fn($t) => ['tarea', $t])->all();
    $errores = collect(self::ERRORES)->map(fn($t) => ['error', $t])->all();
    $funciones = collect(self::FUNCIONES)->map(fn($t) => ['funcion', $t])->all();

    return match ($modo) {
      'hecho' => [...$historias, ...$tareas],
      'por_hacer' => [...$historias, ...$funciones],
      'backlog' => [...$historias, ...$funciones, ...$errores],
      default => [...$historias, ...$tareas, ...$errores, ...$funciones],
    };
  }

  private function resolveStatus(string $modo): string
  {
    return match ($modo) {
      'hecho' => 'hecho',
      'por_hacer' => 'por_hacer',
      'backlog' => 'backlog',
      default => collect(['por_hacer', 'en_progreso', 'en_revision', 'hecho'])->random(),
    };
  }
}
