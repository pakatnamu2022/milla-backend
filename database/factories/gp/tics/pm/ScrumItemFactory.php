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
      'story_points'    => $this->faker->randomElement([1, 2, 3, 5, 8, 13, null]),
      'estimated_hours' => $this->faker->randomFloat(1, 0.5, 40),
      'actual_hours'    => $this->faker->boolean(60) ? $this->faker->randomFloat(1, 0.5, 50) : null,
      'order'           => $this->faker->numberBetween(0, 100),
      'due_date'        => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d') : null,
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
