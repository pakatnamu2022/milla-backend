<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // Primero obtener el ID del tipo CLASS_TYPE VEHICLE
    $vehicleTypeId = DB::table('ap_commercial_masters')
      ->where('type', 'CLASS_TYPE')
      ->where('code', '0')
      ->value('id');

    if (!$vehicleTypeId) {
      throw new \Exception('No se encontró el registro CLASS_TYPE VEHICLE. Ejecutar ApCommercialMastersClassTypeSeeder primero.');
    }

    Schema::table('ap_vehicle_brand', function (Blueprint $table) use ($vehicleTypeId) {
      // Agregar columna con default VEHICLE
      $table->foreignId('type_class_id')
        ->default($vehicleTypeId)
        ->after('type_operation_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnDelete();

      // Crear índice para búsquedas rápidas
      $table->index('type_class_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_brand', function (Blueprint $table) {
      $table->dropForeign(['type_class_id']);
      $table->dropIndex(['type_class_id']);
      $table->dropColumn('type_class_id');
    });
  }
};
