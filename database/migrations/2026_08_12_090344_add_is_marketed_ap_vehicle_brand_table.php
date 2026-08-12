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
      $table->boolean('is_marketed')->default(false)->after('type_class_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_brand', function (Blueprint $table) {
      $table->dropColumn('is_marketed');
    });
  }
};
