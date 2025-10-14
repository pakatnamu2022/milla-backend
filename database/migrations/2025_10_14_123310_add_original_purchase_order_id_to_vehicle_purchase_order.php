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
      $table->unsignedBigInteger('original_purchase_order_id')
        ->nullable()
        ->after('id')
        ->comment('ID de la OC original cuando esta es una corrección por NC');

      $table->foreign('original_purchase_order_id')
        ->references('id')
        ->on('ap_vehicle_purchase_order')
        ->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->dropForeign(['original_purchase_order_id']);
      $table->dropColumn('original_purchase_order_id');
    });
  }
};
