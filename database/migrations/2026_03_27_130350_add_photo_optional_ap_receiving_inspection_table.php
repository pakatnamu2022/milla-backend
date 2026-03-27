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
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->string('photo_optional_1_url')->nullable()->after('photo_right_url');
      $table->string('photo_optional_2_url')->nullable()->after('photo_optional_1_url');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->dropColumn('photo_optional_1_url');
      $table->dropColumn('photo_optional_2_url');
    });
  }
};
