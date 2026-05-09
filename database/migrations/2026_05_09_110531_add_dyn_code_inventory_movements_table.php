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
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->string('movement_number_dyn')->nullable()->after('movement_number')->comment('Número de movimiento generado por Dynamics');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->dropColumn('movement_number_dyn');
    });
  }
};
