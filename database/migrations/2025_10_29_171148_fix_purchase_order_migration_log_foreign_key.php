<?php

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
    // 0. Limpiar registros huérfanos (IDs que no existen en ap_purchase_order)
    DB::table('ap_vehicle_purchase_order_migration_log')->delete();

    // 1. Agregar el foreign key correcto apuntando a ap_purchase_order
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      $table->foreignId('vehicle_purchase_order_id')
        ->after('id')
        ->constrained('ap_purchase_order', 'id', 'fk_vehicle_purchase_log_vh_po_id')
        ->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Eliminar el foreign key
      $table->dropForeign('fk_vehicle_purchase_log_vh_po_id');
      $table->dropColumn('vehicle_purchase_order_id');
    });
  }
};
