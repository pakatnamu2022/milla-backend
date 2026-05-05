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
      $table->boolean('is_delivery')->default(false)->after('actual_delivery_date');
      $table->integer('delivery_by')->nullable()->after('is_delivery');
      $table->foreign('delivery_by')->references('id')->on('usr_users');
      $table->json('post_service_follow_up')->nullable()->after('created_by');
      $table->dropColumn('actual_delivery_time');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropForeign(['delivery_by']);
      $table->dropColumn(['is_delivery', 'post_service_follow_up', 'delivery_by']);
      $table->time('actual_delivery_time')->nullable()->after('actual_delivery_date');
    });
  }
};
