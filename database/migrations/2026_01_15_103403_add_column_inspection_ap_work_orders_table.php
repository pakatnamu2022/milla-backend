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
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->foreignId('vehicle_inspection_id')->nullable()->after('appointment_planning_id')->constrained('ap_vehicle_inspection')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropForeign(['vehicle_inspection_id']);
      $table->dropColumn('vehicle_inspection_id');
    });
  }
};
