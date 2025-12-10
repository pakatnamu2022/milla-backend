<?php

namespace Database\Seeders\ap\commercial;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * php artisan db:seed --class=Database\Seeders\ap\commercial\ApCommercialMastersClassTypeSeeder
 */
class ApCommercialMastersClassTypeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * Crea los registros de tipo CLASS_TYPE en ap_commercial_masters
   * para clasificar marcas y clases de artículos
   */
  public function run(): void
  {
    $classTypes = [
      [
        'code' => '0',
        'description' => 'VEHICLE',
        'type' => 'CLASS_TYPE',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'code' => '1',
        'description' => 'CAMION',
        'type' => 'CLASS_TYPE',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ],
    ];

    foreach ($classTypes as $classType) {
      DB::table('ap_commercial_masters')->updateOrInsert(
        [
          'type' => $classType['type'],
          'code' => $classType['code'],
        ],
        $classType
      );
    }

    $this->command->info('✓ CLASS_TYPE registros creados exitosamente');
  }
}
