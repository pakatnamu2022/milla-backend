<?php

namespace Database\Seeders;

use Database\Seeders\ap\compras\UnitMeasurementSeeder;
use Database\Seeders\gp\views\ViewSeeder;
use Illuminate\Database\Seeder;

// php artisan db:seed
class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
//    $this->call(UnitMeasurementSeeder::class);
    $this->call(ViewSeeder::class);
    $this->call(CrudPermissionsSeeder::class);

  }
}
