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
    Schema::table('products', function (Blueprint $table) {
      // Eliminar restricción única de 'code'
      $table->dropUnique(['code']);

      // Eliminar restricción única de 'dyn_code'
      $table->dropUnique(['dyn_code']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('products', function (Blueprint $table) {
      // Restaurar restricción única de 'code'
      $table->unique('code');

      // Restaurar restricción única de 'dyn_code'
      $table->unique('dyn_code');
    });
  }
};
