<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('help_asig_equipos', function (Blueprint $table) {
      $table->text('observacion')->nullable()->after('unassigned_at');
      $table->text('observacion_unassign')->nullable()->after('observacion');
      $table->string('pdf_path')->nullable()->after('observacion_unassign');
      $table->string('pdf_unassign_path')->nullable()->after('pdf_path');
    });
  }

  public function down(): void
  {
    Schema::table('help_asig_equipos', function (Blueprint $table) {
      $table->dropColumn(['observacion', 'observacion_unassign', 'pdf_path', 'pdf_unassign_path']);
    });
  }
};
