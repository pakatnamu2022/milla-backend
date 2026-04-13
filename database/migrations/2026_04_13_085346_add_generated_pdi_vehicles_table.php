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
    Schema::table('ap_vehicles', function (Blueprint $table) {
      $table->integer('year_delivery')->nullable()->after('year');
      $table->boolean('generated_pdi')->default(false)->after('has_pdi');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicles', function (Blueprint $table) {
      $table->dropColumn(['year_delivery', 'generated_pdi']);
    });
  }
};
