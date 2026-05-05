<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // fecha_contrato tiene valores '0000-00-00' que invalidan strict mode al agregar FK
    $originalModes = DB::select("SELECT @@sql_mode as sql_mode")[0]->sql_mode;
    DB::statement("SET sql_mode = REPLACE(REPLACE('" . $originalModes . "', 'STRICT_TRANS_TABLES', ''), 'STRICT_ALL_TABLES', '')");

    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->integer('second_boss_id')->nullable()->after('jefe_id');
      $table->foreign('second_boss_id')->references('id')->on('rrhh_persona');
    });

    DB::statement("SET sql_mode = '" . $originalModes . "'");
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->dropForeign(['second_boss_id']);
      $table->dropColumn('second_boss_id');
    });
  }
};
