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
    Schema::table('work_order_planning', function (Blueprint $table) {
      $table->string('canceled_note')->nullable()->after('work_order_id');
      $table->integer('canceled_by')->nullable()->after('canceled_note');
      $table->timestamp('canceled_at')->nullable()->after('canceled_by');

      $table->foreign('canceled_by')->references('id')->on('usr_users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('work_order_planning', function (Blueprint $table) {
      $table->dropForeign(['canceled_by']);
      $table->dropColumn('canceled_note');
      $table->dropColumn('canceled_by');
      $table->dropColumn('canceled_at');
    });
  }
};
