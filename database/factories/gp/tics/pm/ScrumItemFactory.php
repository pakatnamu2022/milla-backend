<?php

namespace Database\Factories\gp\tics\pm;

use App\Models\User;
use App\Models\gp\tics\pm\ScrumProject;
use App\Models\gp\tics\pm\ScrumSprint;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScrumItemFactory extends Factory
{
  public function definition(): array
  {
    $status   = $this->faker->randomElement(['backlog', 'por_hacer', 'en_progreso', 'en_revision', 'hecho']);
    $closedAt = $status === 'hecho' ? $this->faker->dateTimeBetween('-30 days', 'now') : null;

    // story_points siempre presente (nunca null): es el peso que usan el
    // Gantt y la curva de esfuerzo para ponderar cada tarea. start_date
    // siempre se genera y due_date siempre es start_date + duración, para
    // que la mayoría de items tengan un rango de fechas usable (antes
    // due_date solo salía 40% de las veces y start_date nunca, así que el
    // Gantt/esfuerzo quedaban casi vacíos).
    $storyPoints = $this->faker->randomElement([1, 2, 3, 5, 8, 13]);
    $startDate   = $this->faker->dateTimeBetween('-30 days', '+1 month');
    $durationDays = max(1, (int) round($storyPoints * $this->faker->randomFloat(2, 0.5, 1.5)));
    $dueDate     = (clone $startDate)->modify("+{$durationDays} days");

    return [
      'project_id'      => ScrumProject::inRandomOrder()->first()?->id ?? ScrumProject::factory(),
      'sprint_id'       => $this->faker->boolean(70) ? ScrumSprint::inRandomOrder()->first()?->id : null,
      'parent_id'       => null,
      'type'            => $this->faker->randomElement(['tarea', 'historia', 'funcion', 'solicitud', 'error']),
      'title'           => $this->faker->sentence(5),
      'description'     => $this->faker->paragraphs(2, true),
      'status'          => $status,
      'priority'        => $this->faker->randomElement(['alta', 'media', 'baja']),
      'assigned_to'     => $this->faker->boolean(80) ? User::inRandomOrder()->first()?->id : null,
      'created_by'      => User::inRandomOrder()->first()?->id ?? 1,
      'story_points'    => $storyPoints,
      'estimated_hours' => $durationDays * 8,
      'actual_hours'    => $this->faker->boolean(60) ? $this->faker->randomFloat(1, 0.5, 50) : null,
      'order'           => $this->faker->numberBetween(0, 100),
      'start_date'      => $startDate->format('Y-m-d'),
      'due_date'        => $dueDate->format('Y-m-d'),
      'closed_at'       => $closedAt,
    ];
  }

  public function enProgreso(): static
  {
    return $this->state(['status' => 'en_progreso', 'closed_at' => null]);
  }

  public function hecho(): static
  {
    return $this->state([
      'status'    => 'hecho',
      'closed_at' => now()->subDays(rand(1, 10)),
    ]);
  }

  public function backlog(): static
  {
    return $this->state(['status' => 'backlog', 'sprint_id' => null, 'closed_at' => null]);
  }
}
