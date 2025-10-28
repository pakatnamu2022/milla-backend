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
    Schema::table('permission', function (Blueprint $table) {
      // Relación con config_vista (módulos/vistas del sistema)
      $table->integer('vista_id')->nullable()->after('module')->comment('ID de la vista/módulo en config_vista');

      // Foreign key constraint
      $table->foreign('vista_id')
        ->references('id')
        ->on('config_vista')
        ->onDelete('set null');

      // Índice para mejorar queries
      $table->index('vista_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('permission', function (Blueprint $table) {
      $table->dropForeign(['vista_id']);
      $table->dropIndex(['vista_id']);
      $table->dropColumn('vista_id');
    });
  }
};
