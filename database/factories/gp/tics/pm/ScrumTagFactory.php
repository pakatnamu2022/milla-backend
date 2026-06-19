<?php

namespace Database\Factories\gp\tics\pm;

use App\Models\gp\tics\pm\ScrumProject;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScrumTagFactory extends Factory
{
  public function definition(): array
  {
    return [
      'project_id' => ScrumProject::inRandomOrder()->first()?->id,
      'name'       => $this->faker->randomElement(['backend', 'frontend', 'urgente', 'bloqueado', 'qa', 'diseño', 'refactor', 'docs', 'deuda-tecnica', 'api']),
      'color'      => $this->faker->randomElement(['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#6B7280']),
    ];
  }
}
