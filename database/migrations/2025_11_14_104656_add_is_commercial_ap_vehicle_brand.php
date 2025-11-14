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
    Schema::table('ap_vehicle_brand', function (Blueprint $table) {
      $table->boolean('is_commercial')->default(false)->after('logo_min');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_brand', function (Blueprint $table) {
      $table->dropColumn('is_commercial');
    });
  }
};
