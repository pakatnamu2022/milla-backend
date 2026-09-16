<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extensiones del RAR (Registro de Acciones de Reclutamiento) sobre
 * `rrhh_proceso_postulacion`:
 * - `solicitante_id`: quien pidió la posición (cliente interno / jefatura).
 * - `prioridad`: para poder priorizar procesos al agregar tiempo adicional.
 * - Pausa individual por proceso (`pausado`, `motivo_pausa`,
 *   `fecha_inicio_pausa`, `dias_pausados` acumulados, `veces_pausado`
 *   contador de reanudaciones) — el tiempo pausado no se contabiliza en el
 *   indicador de cobertura. El detalle de cada pausa/reanudación queda
 *   además registrado en `rrhh_proceso_historial`.
 *
 * `rrhh_proceso_historial`: trazabilidad de pausas/reanudaciones/ampliaciones
 * de plazo por proceso (quién, cuándo, motivo).
 *
 * `rrhh_mensaje_estado`: mensajes automáticos editables por Gestión Humana,
 * uno por cada valor de `Applicant::TIPO_*`, que se envían al postulante
 * cuando cambia de estado.
 */
return new class extends Migration {
  public function up(): void
  {
    // Sin foreign key hacia usr_users: rrhh_proceso_postulacion tiene filas legacy
    // con fecha_inicio = '0000-00-00', y agregar un constraint fuerza a MySQL a
    // reconstruir/validar la tabla completa (ALGORITHM=COPY), lo que revienta con
    // "Incorrect date value" en modo estricto. La integridad de solicitante_id se
    // valida a nivel de aplicación (Request: exists:usr_users,id).
    Schema::table('rrhh_proceso_postulacion', function (Blueprint $table) {
      $table->integer('solicitante_id')->nullable()->after('cargo_id');
      $table->unsignedTinyInteger('prioridad')->default(0)->after('solicitante_id');
      $table->boolean('pausado')->default(false)->after('status_id');
      $table->text('motivo_pausa')->nullable()->after('pausado');
      $table->dateTime('fecha_inicio_pausa')->nullable()->after('motivo_pausa');
      $table->integer('dias_pausados')->default(0)->after('fecha_inicio_pausa');
      $table->unsignedInteger('veces_pausado')->default(0)->after('dias_pausados');
    });

    Schema::create('rrhh_proceso_historial', function (Blueprint $table) {
      $table->id();
      $table->integer('proceso_postulacion_id');
      $table->string('accion');
      $table->text('detalle')->nullable();
      $table->integer('dias_agregados')->nullable();
      $table->integer('usuario_id')->nullable();
      $table->timestamps();

      $table->foreign('proceso_postulacion_id')
        ->references('id')
        ->on('rrhh_proceso_postulacion')
        ->onDelete('cascade');

      $table->foreign('usuario_id')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->index(['proceso_postulacion_id', 'created_at']);
    });

    Schema::create('rrhh_mensaje_estado', function (Blueprint $table) {
      $table->id();
      $table->unsignedTinyInteger('tipo_trabajador_id')->unique();
      $table->string('asunto');
      $table->text('contenido');
      $table->boolean('activo')->default(true);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('rrhh_mensaje_estado');
    Schema::dropIfExists('rrhh_proceso_historial');

    Schema::table('rrhh_proceso_postulacion', function (Blueprint $table) {
      $table->dropColumn(['solicitante_id', 'prioridad', 'pausado', 'motivo_pausa', 'fecha_inicio_pausa', 'dias_pausados', 'veces_pausado']);
    });
  }
};
