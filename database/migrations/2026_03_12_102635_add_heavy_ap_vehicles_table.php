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
      $table->boolean('is_heavy')->default(false)->after('customer_id')->comment('Es vehiculo pesado');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicles', function (Blueprint $table) {
      $table->dropColumn('is_heavy');
    });
  }
};
