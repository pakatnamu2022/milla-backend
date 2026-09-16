<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue la fase de la entrevista (RRHH=1, Jefe=2), ya que el flujo real
 * del proceso de reclutamiento pasa por dos entrevistas por postulante: una
 * con RRHH y, si avanza, otra con la jefatura solicitante. La entrevista de
 * jefe solo puede registrarse si ya existe una de RRHH calificada para ese
 * postulante en el mismo proceso (ver InterviewService::store).
 */
return new class extends Migration {
  public function up(): void
  {
    Schema::table('rrhh_entrevista', function (Blueprint $table) {
      $table->unsignedTinyInteger('fase')->default(1)->after('persona_id');
    });

    Schema::table('rrhh_entrevista', function (Blueprint $table) {
      $table->unique(['proceso_postulacion_id', 'persona_id', 'fase'], 'unique_proceso_persona_fase_entrevista');
    });

    Schema::table('rrhh_entrevista', function (Blueprint $table) {
      $table->dropUnique('unique_proceso_persona_entrevista');
    });
  }

  public function down(): void
  {
    Schema::table('rrhh_entrevista', function (Blueprint $table) {
      $table->unique(['proceso_postulacion_id', 'persona_id'], 'unique_proceso_persona_entrevista');
    });

    Schema::table('rrhh_entrevista', function (Blueprint $table) {
      $table->dropUnique('unique_proceso_persona_fase_entrevista');
    });

    Schema::table('rrhh_entrevista', function (Blueprint $table) {
      $table->dropColumn('fase');
    });
  }
};
