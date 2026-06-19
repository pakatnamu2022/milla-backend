<?php

namespace Database\Factories\gp\tics\pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScrumProjectFactory extends Factory
{
  public function definition(): array
  {
    return [
      'name'        => $this->faker->unique()->words(3, true),
      'description' => $this->faker->sentence(10),
      'color'       => $this->faker->randomElement(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899']),
      'status'      => $this->faker->randomElement(['activo', 'activo', 'activo', 'archivado']),
      'created_by'  => User::inRandomOrder()->first()?->id ?? User::factory(),
    ];
  }

  public function activo(): static
  {
    return $this->state(['status' => 'activo']);
  }
}
