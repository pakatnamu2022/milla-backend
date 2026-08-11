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
    Schema::table('work_order_labour', function (Blueprint $table) {
      $table->integer('registered_by')->nullable()->after('work_order_id');
      $table->foreign('registered_by')->references('id')->on('usr_users');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('work_order_labour', function (Blueprint $table) {
      $table->dropForeign('work_order_labour_registered_by_foreign');
      $table->dropColumn('registered_by');
    });
  }
};
