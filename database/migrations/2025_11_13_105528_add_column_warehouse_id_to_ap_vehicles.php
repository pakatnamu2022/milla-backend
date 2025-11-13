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
      $table->foreignId('warehouse_id')->after('ap_models_vn_id')->nullable()->constrained('warehouse');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicles', function (Blueprint $table) {
      $table->dropForeign(['warehouse_id']);
      $table->dropColumn('warehouse_id');
    });
  }
};
