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
    Schema::table('ap_purchase_order', function (Blueprint $table) {
      $table->integer('created_by');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_purchase_order', function (Blueprint $table) {
      $table->dropForeign(['created_by']);
      $table->dropColumn('created_by');
    });
  }
};
