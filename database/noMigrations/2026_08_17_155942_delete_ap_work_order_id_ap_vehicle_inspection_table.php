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
      $table->dropColumn('is_cancelled');
      $table->dropColumn('cancellation_requested_at');
      $table->dropColumn('cancellation_confirmed_at');
      $table->dropColumn('cancellation_reason');
      $table->dropForeign(['cancellation_requested_by']);
      $table->dropColumn('cancellation_requested_by');
      $table->dropForeign(['cancellation_confirmed_by']);
      $table->dropColumn('cancellation_confirmed_by');
      $table->dropForeign(['ap_work_order_id']);
      $table->dropColumn('ap_work_order_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->boolean('is_cancelled')->default(false);
      $table->dateTime('cancellation_requested_at')->nullable();
      $table->dateTime('cancellation_confirmed_at')->nullable();
      $table->dateTime('cancellation_reason')->nullable();
      $table->foreignId('ap_work_order_id')->nullable()->constrained('ap_work_orders');
    });
  }
};
