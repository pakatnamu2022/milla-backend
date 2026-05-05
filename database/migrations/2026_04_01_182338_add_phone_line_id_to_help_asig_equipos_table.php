<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('help_asig_equipos', function (Blueprint $table) {
      $table->unsignedBigInteger('phone_line_id')->nullable()->after('persona_id')
        ->comment('Línea telefónica vinculada a esta asignación (opcional)');
      $table->foreign('phone_line_id')->references('id')->on('phone_line')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('help_asig_equipos', function (Blueprint $table) {
      $table->dropForeign(['phone_line_id']);
      $table->dropColumn('phone_line_id');
    });
  }
};
