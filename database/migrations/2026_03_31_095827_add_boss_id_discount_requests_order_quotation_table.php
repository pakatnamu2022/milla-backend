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
    Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
      $table->integer('boss_id')->nullable()->after('manager_id');
      $table->foreign('boss_id')->references('id')->on('usr_users')->onDelete('cascade');
      $table->integer('advisor_id')->nullable()->after('boss_id');
      $table->foreign('advisor_id')->references('id')->on('usr_users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
      $table->dropForeign(['boss_id']);
      $table->dropColumn('boss_id');
      $table->dropForeign(['advisor_id']);
      $table->dropColumn('advisor_id');
    });
  }
};
