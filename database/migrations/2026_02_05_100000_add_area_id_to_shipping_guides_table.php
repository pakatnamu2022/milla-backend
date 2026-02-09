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
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->foreignId('area_id')->default(826)->constrained('ap_masters')->after('status');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->dropForeign(['area_id']);
      $table->dropColumn('area_id');
    });
  }
};
