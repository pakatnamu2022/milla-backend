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
    Schema::table('gh_payroll_schedules', function (Blueprint $table) {
      // Crear índice simple primero
      $table->index('worker_id', 'idx_worker_id');

      // Luego eliminar el unique
      $table->dropUnique('unique_worker_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_payroll_schedules', function (Blueprint $table) {
      // Restaurar unique
      $table->unique(['worker_id', 'work_date'], 'unique_worker_date');

      // Eliminar índice simple
      $table->dropIndex('idx_worker_id');
    });
  }
};
