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
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->dropForeign(['ap_vehicle_purchase_order_id']);
      $table->dropColumn('ap_vehicle_purchase_order_id');
      $table->foreignId('ap_vehicle_id')->after('id')->constrained('ap_vehicles')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->foreignId('ap_vehicle_purchase_order_id')->after('ap_vehicle_status_id')->constrained('ap_vehicle_purchase_order')->onDelete('cascade');
      $table->dropForeign(['ap_vehicle_id']);
      $table->dropColumn('ap_vehicle_id');
    });
  }
};
