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
      $table->integer('reviewed_by')->nullable()->after('warehouse_id');
      $table->foreign('reviewed_by')->references('id')->on('usr_users');
      $table->datetime('reviewed_at')->nullable()->after('reviewed_by');
      $table->boolean('approved')->default(false)->after('reviewed_at');
      $table->enum('status', ['pending', 'ordered', 'received', 'cancelled'])->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
      $table->dropForeign(['reviewed_by']);
      $table->dropColumn('reviewed_by');
      $table->dropColumn('reviewed_at');
      $table->dropColumn('approved');
    });
  }
};
