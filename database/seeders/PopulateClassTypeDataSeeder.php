<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * php artisan db:seed --class=Database\Seeders\PopulateClassTypeDataSeeder
 */
class PopulateClassTypeDataSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * Poblar datos existentes con type_class_id
   */
  public function run(): void
  {
    // Obtener IDs de los tipos de clase
    $vehicleTypeId = DB::table('ap_masters')
      ->where('type', 'CLASS_TYPE')
      ->where('code', '0')
      ->value('id');

    $camionTypeId = DB::table('ap_masters')
      ->where('type', 'CLASS_TYPE')
      ->where('code', '1')
      ->value('id');

    if (!$vehicleTypeId || !$camionTypeId) {
      $this->command->error('✗ No se encontraron los tipos CLASS_TYPE. Ejecutar ApCommercialMastersClassTypeSeeder primero.');
      return;
    }

    // Actualizar marcas
    $this->command->info('Actualizando marcas...');

    // JAC CAMIONES (id=9) → CAMION
    $updatedBrands = DB::table('ap_vehicle_brand')
      ->where('id', 9)
      ->update(['type_class_id' => $camionTypeId]);

    $this->command->info("✓ {$updatedBrands} marca(s) actualizada(s) a CAMION");

    // Las demás marcas ya tienen default VEHICLE por la migración
    $this->command->info('✓ Las demás marcas tienen VEHICLE por defecto');

    // Actualizar clases de artículos
    $this->command->info('Actualizando clases de artículos...');

    // VEHICLE (clases 3, 5)
    $vehicleClasses = DB::table('ap_class_article')
      ->whereIn('id', [3, 5]) // VEHICULOS NUEVO, VEHICULO USADO
      ->update(['type_class_id' => $vehicleTypeId]);

    $this->command->info("✓ {$vehicleClasses} clase(s) actualizada(s) a VEHICLE");

    // CAMION (clases 4, 6)
    $camionClasses = DB::table('ap_class_article')
      ->whereIn('id', [4, 6]) // CAMIONES NUEVO, CAMIONES USADO
      ->update(['type_class_id' => $camionTypeId]);

    $this->command->info("✓ {$camionClasses} clase(s) actualizada(s) a CAMION");

    // Las demás clases permanecen NULL (sin clasificar)
    $this->command->info('✓ Resto de clases permanecen sin clasificar (NULL)');

    $this->command->info('✓ Datos poblados exitosamente');
  }
}
