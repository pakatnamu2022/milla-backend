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
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->dropForeign(['work_order_id']);
      $table->dropColumn('work_order_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->foreignId('work_order_id')->after('id')->constrained('ap_work_orders')->onUpdate('cascade')->onDelete('cascade');
    });
  }
};
