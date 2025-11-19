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
    Schema::table('ap_purchase_order', function (Blueprint $table) {
      $table->string('payment_term')->nullable()->after('total');
      $table->foreignId('type_operation_id')->nullable()->after('vehicle_movement_id')
        ->constrained('ap_commercial_masters');
    });

    // Cambiamos a nullable los campos unit_measurement_id y description en ap_purchase_order_item
    Schema::table('ap_purchase_order_item', function (Blueprint $table) {
      $table->foreignId('unit_measurement_id')->nullable()->change();
      $table->string('description')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_purchase_order', function (Blueprint $table) {
      $table->dropColumn('payment_term');
      $table->dropForeign(['type_operation_id']);
      $table->dropColumn('type_operation_id');
    });

    // Revertir los cambios de unit_measurement_id y description
    Schema::table('ap_purchase_order_item', function (Blueprint $table) {
      $table->foreignId('unit_measurement_id')->nullable(false)->change();
      $table->string('description')->nullable(false)->change();
    });
  }
};
