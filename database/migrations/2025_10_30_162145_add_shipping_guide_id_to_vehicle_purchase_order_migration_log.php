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
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Hacer nullable el campo vehicle_purchase_order_id
      $table->unsignedBigInteger('vehicle_purchase_order_id')->nullable()->change();
      $table->foreignId('ap_vehicles_id')->nullable()->after('vehicle_purchase_order_id')
        ->constrained('ap_vehicles');
      // Agregar el campo shipping_guide_id
      $table->foreignId('shipping_guide_id')->nullable()->after('ap_vehicles_id')
        ->constrained('shipping_guides', 'id', 'fk_shipping_guide');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Eliminar por el nombre exacto del constraint
      $table->dropForeign('fk_shipping_guide');
      $table->dropForeign('ap_vehicle_id');

      // Eliminar la columnas
      $table->dropColumn('shipping_guide_id');
      $table->dropColumn('ap_vehicles_id');

      // Revertir nullable del vehicle_purchase_order_id
      $table->unsignedBigInteger('vehicle_purchase_order_id')->nullable(false)->change();
    });
  }
};
