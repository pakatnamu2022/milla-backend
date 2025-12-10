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
    Schema::table('ap_commercial_manager_brand_group_periods', function (Blueprint $table) {
      $table->boolean('status')->default(true);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_commercial_manager_brand_group_periods', function (Blueprint $table) {
      $table->dropColumn('status');
    });
  }
};
