<?php

namespace Database\Seeders;

use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApClassArticleAccountMappingSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $mappings = [
      // VEHICULO NUEVO
      ['dyn_code' => 'M_VEH_NUE', 'type' => 'PRECIO', 'origin' => '4961100', 'is_debit_origin' => true, 'destination' => '7011111'],
      ['dyn_code' => 'M_VEH_NUE', 'type' => 'DESCUENTO', 'origin' => '4962100', 'is_debit_origin' => false, 'destination' => '7411000'],

      // CAMION NUEVO
      ['dyn_code' => 'M_CAM_NUE', 'type' => 'PRECIO', 'origin' => '4961200', 'is_debit_origin' => true, 'destination' => '7011112'],
      ['dyn_code' => 'M_CAM_NUE', 'type' => 'DESCUENTO', 'origin' => '4962200', 'is_debit_origin' => false, 'destination' => '7411000'],

      // VEHICULO USADO
      ['dyn_code' => 'M_VEH_USA', 'type' => 'PRECIO', 'origin' => '4961300', 'is_debit_origin' => true, 'destination' => '7011113'],
      ['dyn_code' => 'M_VEH_USA', 'type' => 'DESCUENTO', 'origin' => '4962300', 'is_debit_origin' => false, 'destination' => '7411000'],

      // CAMION USADO
      ['dyn_code' => 'M_CAM_USA', 'type' => 'PRECIO', 'origin' => '4961400', 'is_debit_origin' => true, 'destination' => '7011114'],
      ['dyn_code' => 'M_CAM_USA', 'type' => 'DESCUENTO', 'origin' => '4962400', 'is_debit_origin' => false, 'destination' => '7411000'],
    ];

    foreach ($mappings as $mapping) {
      $classArticle = ApClassArticle::where('dyn_code', $mapping['dyn_code'])->first();

      if (!$classArticle) {
        $this->command->warn("No se encontró la clase de artículo: {$mapping['dyn_code']}");
        continue;
      }

      DB::table('ap_class_article_account_mapping')->updateOrInsert(
        [
          'ap_class_article_id' => $classArticle->id,
          'account_type' => $mapping['type'],
        ],
        [
          'account_origin' => $mapping['origin'],
          'account_destination' => $mapping['destination'],
          'is_debit_origin' => $mapping['is_debit_origin'],
          'status' => true,
          'created_at' => now(),
          'updated_at' => now(),
        ]
      );

      $this->command->info("Mapeo creado: {$mapping['dyn_code']} - {$mapping['type']}");
    }

    $this->command->info('Seeder de mapeo de cuentas completado exitosamente.');
  }
}
