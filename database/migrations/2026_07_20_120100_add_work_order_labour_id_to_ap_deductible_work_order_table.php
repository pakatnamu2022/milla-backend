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
    Schema::table('ap_deductible_work_order', function (Blueprint $table) {
      $table->unsignedBigInteger('work_order_labour_id')->nullable()->after('electronic_document_id');

      $table->foreign('work_order_labour_id')
        ->references('id')
        ->on('work_order_labour')
        ->onDelete('set null');

      $table->index('work_order_labour_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_deductible_work_order', function (Blueprint $table) {
      $table->dropForeign(['work_order_labour_id']);
      $table->dropColumn('work_order_labour_id');
    });
  }
};