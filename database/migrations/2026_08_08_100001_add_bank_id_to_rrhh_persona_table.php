<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * Normaliza el banco de haberes (hoy texto libre en entidad_haberes, con ~20 variantes
   * distintas para los mismos bancos) a una FK contra el catalogo bancos ya existente.
   * entidad_haberes se conserva como respaldo/historico, no se elimina en esta migracion.
   */
  public function up(): void
  {
    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->integer('bank_id')->nullable()->after('entidad_haberes');
    });

    // rrhh_persona tiene filas legacy con fechas '0000-00-00' que rompen el ALTER TABLE
    // (agregar la FK reescribe la tabla y MySQL revalida todas las columnas en modo estricto).
    // No es un problema de esta columna, se baja el sql_mode solo para esta sentencia.
    $previousSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode as mode')->mode;
    DB::statement("SET SESSION sql_mode=''");
    try {
      DB::statement('ALTER TABLE rrhh_persona ADD CONSTRAINT rrhh_persona_bank_id_foreign FOREIGN KEY (bank_id) REFERENCES bancos (id) ON DELETE SET NULL');
    } finally {
      DB::statement("SET SESSION sql_mode='{$previousSqlMode}'");
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->dropForeign(['bank_id']);
      $table->dropColumn('bank_id');
    });
  }
};
