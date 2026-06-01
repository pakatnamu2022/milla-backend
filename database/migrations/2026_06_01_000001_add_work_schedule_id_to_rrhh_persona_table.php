<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    $originalModes = DB::select("SELECT @@sql_mode as sql_mode")[0]->sql_mode;
    DB::statement("SET sql_mode = REPLACE(REPLACE('" . $originalModes . "', 'STRICT_TRANS_TABLES', ''), 'STRICT_ALL_TABLES', '')");

    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->unsignedBigInteger('work_schedule_id')->nullable()->after('horas_jornada');
      $table->foreign('work_schedule_id')->references('id')->on('work_schedules')->nullOnDelete();
    });

    DB::statement("SET sql_mode = '" . $originalModes . "'");
  }

  public function down(): void
  {
    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->dropForeign(['work_schedule_id']);
      $table->dropColumn('work_schedule_id');
    });
  }
};
