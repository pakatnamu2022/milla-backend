<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etapa de entrevista del proceso de reclutamiento (RAR): permite configurar,
 * por proceso, qué subcompetencias (gh_config_subcompetencias) se evalúan en
 * la entrevista, y registrar una entrevista con su calificación por
 * subcompetencia para cada postulante que avanza de "postulante" a
 * "entrevista". El resultado promedio de la entrevista se guarda en
 * rrhh_entrevista.resultado_promedio, calculado a partir de las filas de
 * rrhh_entrevista_calificacion.
 */
return new class extends Migration {
  public function up(): void
  {
    Schema::create('rrhh_proceso_competencia', function (Blueprint $table) {
      $table->id();
      $table->integer('proceso_postulacion_id');
      $table->unsignedBigInteger('sub_competencia_id');
      $table->integer('orden')->nullable();
      $table->timestamps();

      $table->foreign('proceso_postulacion_id')
        ->references('id')
        ->on('rrhh_proceso_postulacion')
        ->onDelete('cascade');

      $table->foreign('sub_competencia_id')
        ->references('id')
        ->on('gh_config_subcompetencias')
        ->onDelete('cascade');

      $table->unique(['proceso_postulacion_id', 'sub_competencia_id'], 'unique_proceso_subcompetencia');
    });

    Schema::create('rrhh_entrevista', function (Blueprint $table) {
      $table->id();
      $table->integer('proceso_postulacion_id');
      $table->integer('persona_id');
      $table->integer('entrevistador_id')->nullable();
      $table->dateTime('fecha_entrevista')->nullable();
      $table->decimal('resultado_promedio', 5, 2)->nullable();
      $table->text('observaciones')->nullable();
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('proceso_postulacion_id')
        ->references('id')
        ->on('rrhh_proceso_postulacion')
        ->onDelete('cascade');

      $table->foreign('persona_id')
        ->references('id')
        ->on('rrhh_persona')
        ->onDelete('cascade');

      $table->foreign('entrevistador_id')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->foreign('updated_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->unique(['proceso_postulacion_id', 'persona_id'], 'unique_proceso_persona_entrevista');
    });

    Schema::create('rrhh_entrevista_calificacion', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('entrevista_id');
      $table->unsignedBigInteger('sub_competencia_id');
      $table->decimal('puntaje', 5, 2)->nullable();
      $table->timestamps();

      $table->foreign('entrevista_id')
        ->references('id')
        ->on('rrhh_entrevista')
        ->onDelete('cascade');

      $table->foreign('sub_competencia_id')
        ->references('id')
        ->on('gh_config_subcompetencias')
        ->onDelete('cascade');

      $table->unique(['entrevista_id', 'sub_competencia_id'], 'unique_entrevista_subcompetencia');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('rrhh_entrevista_calificacion');
    Schema::dropIfExists('rrhh_entrevista');
    Schema::dropIfExists('rrhh_proceso_competencia');
  }
};
