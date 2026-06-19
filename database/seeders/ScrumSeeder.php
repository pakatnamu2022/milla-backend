<?php

namespace Database\Seeders;

use App\Models\gp\tics\pm\ScrumItem;
use App\Models\gp\tics\pm\ScrumProject;
use App\Models\gp\tics\pm\ScrumSprint;
use App\Models\gp\tics\pm\ScrumTag;
use Illuminate\Database\Seeder;

class ScrumSeeder extends Seeder
{
  public function run(): void
  {
    // 3 proyectos activos
    $projects = ScrumProject::factory(3)->activo()->create();

    foreach ($projects as $project) {
      // Tags por proyecto
      $tags = ScrumTag::factory(5)->create(['project_id' => $project->id]);

      // 1 sprint cerrado + 1 activo + 1 planeado
      $sprintCerrado = ScrumSprint::factory()->cerrado()->create(['project_id' => $project->id, 'name' => 'Sprint 1']);
      $sprintActivo = ScrumSprint::factory()->activo()->create(['project_id' => $project->id, 'name' => 'Sprint 2']);
      $sprintPlan = ScrumSprint::factory()->create(['project_id' => $project->id, 'name' => 'Sprint 3', 'status' => 'planeado']);

      // Items en sprint cerrado (mayormente hechos)
      ScrumItem::factory(8)->hecho()->create([
        'project_id' => $project->id,
        'sprint_id'  => $sprintCerrado->id,
      ])->each(fn($item) => $item->tags()->attach($tags->random(rand(0, 2))->pluck('id')));

      // Items en sprint activo (mix de estados)
      ScrumItem::factory(10)->create([
        'project_id' => $project->id,
        'sprint_id'  => $sprintActivo->id,
      ])->each(fn($item) => $item->tags()->attach($tags->random(rand(0, 2))->pluck('id')));

      // Items en backlog (sin sprint)
      ScrumItem::factory(6)->backlog()->create([
        'project_id' => $project->id,
        'sprint_id'  => null,
      ]);

      // Algunos items hijos (tareas de una historia)
      $historias = ScrumItem::where('project_id', $project->id)
        ->where('type', 'historia')
        ->take(2)
        ->get();

      foreach ($historias as $historia) {
        ScrumItem::factory(3)->create([
          'project_id' => $project->id,
          'sprint_id'  => $historia->sprint_id,
          'parent_id'  => $historia->id,
          'type'       => 'tarea',
        ]);
      }
    }
  }
}
