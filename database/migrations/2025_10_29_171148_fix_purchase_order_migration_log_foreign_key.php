<?php

use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // 0. Limpiar registros (truncate)
    VehiclePurchaseOrderMigrationLog::query()->truncate();

    // 1. Modificar el foreign key para que apunte a ap_purchase_order
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Eliminar el foreign key existente usando el nombre correcto
      $table->dropForeign('vehicle_purchase_order_id');
      $table->dropColumn('vehicle_purchase_order_id');

      // Crear la nueva columna con el foreign key correcto
      $table->foreignId('vehicle_purchase_order_id')
        ->after('id')
        ->constrained('ap_purchase_order', 'id', 'fk_vehicle_purchase_log_vh_po_id')
        ->onDelete('cascade');
    });

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Eliminar el foreign key
      $table->dropForeign('fk_vehicle_purchase_log_vh_po_id');
      $table->dropColumn('vehicle_purchase_order_id');

      // Restaurar el foreign key original
      $table->foreignId('vehicle_purchase_order_id')
        ->after('id')
        ->constrained('ap_vehicle_purchase_order', 'id', 'vehicle_purchase_order_id')
        ->onDelete('cascade');
    });

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
  }
};
