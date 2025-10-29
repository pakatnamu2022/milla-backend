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
    // 0. Limpiar registros huérfanos
    DB::table('ap_vehicle_purchase_order_migration_log')->delete();

    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {

      if (!Schema::hasColumn('ap_vehicle_purchase_order_migration_log', 'vehicle_purchase_order_id')) {
        $table->unsignedBigInteger('vehicle_purchase_order_id')->after('id');
      }

      if (!Schema::hasColumn('ap_vehicle_purchase_order_migration_log', 'fk_vehicle_purchase_log_vh_po_id')) {
        $table->foreign('vehicle_purchase_order_id', 'fk_vehicle_purchase_log_vh_po_id')
          ->references('id')
          ->on('ap_purchase_order')
          ->onDelete('cascade');
      }
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {

      // 1. Primero eliminar FK con el NOMBRE REAL exacto
      $table->dropForeign('vehicle_purchase_order_id');

      // 2. Recién luego eliminar la columna
      $table->dropColumn('vehicle_purchase_order_id');
    });
  }
};
