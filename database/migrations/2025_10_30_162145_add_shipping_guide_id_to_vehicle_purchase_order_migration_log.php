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

      // Agregar el campo shipping_guide_id
      $table->foreignId('shipping_guide_id')
        ->constrained('shipping_guides', 'id', 'fk_shipping_guide');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Eliminar la foreign key y el campo
      $table->dropForeign(['shipping_guide_id']);
      $table->dropColumn('shipping_guide_id');

      // Revertir nullable del vehicle_purchase_order_id (hacerlo NOT NULL nuevamente)
      $table->unsignedBigInteger('vehicle_purchase_order_id')->nullable(false)->change();
    });
  }
};
