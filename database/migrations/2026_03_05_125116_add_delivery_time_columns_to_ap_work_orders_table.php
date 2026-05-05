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
      $table->time('estimated_delivery_time')->nullable()->after('estimated_delivery_date');
      $table->time('actual_delivery_time')->nullable()->after('actual_delivery_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropColumn(['estimated_delivery_time', 'actual_delivery_time']);
    });
  }
};
