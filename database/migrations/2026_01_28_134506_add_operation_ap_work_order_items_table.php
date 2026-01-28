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
    Schema::table('ap_work_order_items', function (Blueprint $table) {
      $table->foreignId('type_operation_id')->nullable()->after('group_number')->constrained('ap_masters')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_order_items', function (Blueprint $table) {
      $table->dropForeign(['type_operation_id']);
      $table->dropColumn('type_operation_id');
    });
  }
};
