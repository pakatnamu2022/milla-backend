<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->unsignedBigInteger('warehouse_id')->nullable()->after('observation')
        ->comment('Almacén en el que queda el vehículo tras este movimiento');
      $table->foreign('warehouse_id')->references('id')->on('warehouse');

      $table->unsignedBigInteger('origin_warehouse_id')->nullable()->after('warehouse_id')
        ->comment('Almacén de origen cuando hay traslado entre almacenes');
      $table->foreign('origin_warehouse_id')->references('id')->on('warehouse');
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->dropForeign(['warehouse_id']);
      $table->dropForeign(['origin_warehouse_id']);
      $table->dropColumn(['warehouse_id', 'origin_warehouse_id']);
    });
  }
};
