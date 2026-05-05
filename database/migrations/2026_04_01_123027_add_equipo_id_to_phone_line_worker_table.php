<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('phone_line_worker', function (Blueprint $table) {
      // Fix column type from bigint (created by failed attempt) to int to match help_equipos.id
      $table->integer('equipo_id')->nullable()->comment('Equipo al que va asociada la línea (opcional)');
      $table->foreign('equipo_id')->references('id')->on('help_equipos')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('phone_line_worker', function (Blueprint $table) {
      $table->dropForeign(['equipo_id']);
      $table->dropColumn('equipo_id');
    });
  }
};
