<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->enum('migration_status', ['pending', 'in_progress', 'completed', 'failed', 'updated_with_nc'])
        ->default('pending')
        ->after('warehouse_physical_id')
        ->comment('Estado de la migración a la BD intermedia');

      $table->timestamp('migrated_at')
        ->nullable()
        ->after('migration_status')
        ->comment('Fecha y hora en que se completó la migración');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->dropColumn(['migration_status', 'migrated_at']);
    });
  }
};
