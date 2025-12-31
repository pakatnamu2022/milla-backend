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
    Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
      $table->integer('requested_by')->nullable()->after('status');
      $table->foreign('requested_by')->references('id')->on('usr_users');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
      $table->dropForeign(['requested_by']);
      $table->dropColumn('requested_by');
    });
  }
};
