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
    Schema::table('user_series_assignment', function (Blueprint $table) {
      // Eliminar la foreign key existente que apunta a rrhh_persona
      $table->dropForeign(['worker_id']);
    });

    Schema::table('user_series_assignment', function (Blueprint $table) {
      // Modificar la columna a integer para que coincida con usr_users.id
      $table->integer('worker_id')->change();

      // Crear la nueva foreign key que apunta a usr_users
      $table->foreign('worker_id')->references('id')->on('usr_users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('user_series_assignment', function (Blueprint $table) {
      // Eliminar la foreign key que apunta a usr_users
      $table->dropForeign(['worker_id']);
    });

    Schema::table('user_series_assignment', function (Blueprint $table) {
      // Revertir la columna a integer
      $table->integer('worker_id')->change();

      // Restaurar la foreign key anterior a rrhh_persona
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');
    });
  }
};
