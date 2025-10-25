<?php

namespace Database\Seeders;

use Database\Seeders\ap\compras\UnitMeasurementSeeder;
use Illuminate\Database\Seeder;

// php artisan db:seed
class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call(UnitMeasurementSeeder::class);
  }
}
