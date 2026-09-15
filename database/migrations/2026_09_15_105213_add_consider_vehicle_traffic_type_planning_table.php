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
      $table->boolean('consider_vehicle_traffic')->default(false)->after('category_type');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('type_planning_work_order', function (Blueprint $table) {
      $table->dropColumn('consider_vehicle_traffic');
    });
  }
};
