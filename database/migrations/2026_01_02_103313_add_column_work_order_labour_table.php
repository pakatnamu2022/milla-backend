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
      $table->integer('group_number')->default(1)->after('id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('work_order_labour', function (Blueprint $table) {
      $table->dropColumn('group_number');
    });
  }
};
