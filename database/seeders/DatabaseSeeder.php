<?php

namespace Database\Seeders;

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
//    $this->call(Competence2Seeder::class);
//    $this->call(TruncateTablesSeeder::class);
//    $this->call(EvaluationModelSeeder::class);
//    $this->call(EvaluationParEvaluatorSeeder::class);
//    $this->call(ApClassArticleAccountMappingSeeder::class);

    // CLASS_TYPE seeders - ejecutar antes de las migraciones de type_class_id
    // php artisan db:seed --class=Database\\Seeders\\ap\\commercial\\ApCommercialMastersClassTypeSeeder
    // Luego ejecutar migraciones: php artisan migrate
    // Finalmente poblar datos: php artisan db:seed --class=Database\\Seeders\\PopulateClassTypeDataSeeder
  }
}
