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
    Schema::table('type_planning_work_order', function (Blueprint $table) {
      $table->string('notes', 255)->nullable()->after('description');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('type_planning_work_order', function (Blueprint $table) {
      $table->dropColumn('notes');
    });
  }
};
