<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->unsignedBigInteger('work_schedule_id')->nullable()->after('horas_jornada');
      $table->foreign('work_schedule_id')->references('id')->on('work_schedules')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->dropForeign(['work_schedule_id']);
      $table->dropColumn('work_schedule_id');
    });
  }
};
