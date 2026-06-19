<?php

namespace Database\Factories\gp\tics\pm;

use App\Models\gp\tics\pm\ScrumProject;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScrumSprintFactory extends Factory
{
  public function definition(): array
  {
    $start = $this->faker->dateTimeBetween('-2 months', '+1 month');
    $end   = (clone $start)->modify('+14 days');

    return [
      'project_id' => ScrumProject::inRandomOrder()->first()?->id ?? ScrumProject::factory(),
      'name'       => 'Sprint ' . $this->faker->numberBetween(1, 20),
      'goal'       => $this->faker->sentence(8),
      'start_date' => $start->format('Y-m-d'),
      'end_date'   => $end->format('Y-m-d'),
      'status'     => $this->faker->randomElement(['planeado', 'activo', 'cerrado']),
    ];
  }

  public function activo(): static
  {
    return $this->state(['status' => 'activo']);
  }

  public function cerrado(): static
  {
    return $this->state(['status' => 'cerrado']);
  }
}
