<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('work_order_planning_sessions', function (Blueprint $table) {
      $table->id();

      // Relación con la planificación
      $table->foreignId('work_order_planning_id')->comment('Planificación de la orden de trabajo')
        ->constrained('work_order_planning')->onDelete('cascade');

      // Registro de inicio y fin de sesión
      $table->dateTime('start_datetime')->comment('Fecha y hora de inicio de la sesión');
      $table->dateTime('end_datetime')->nullable()->comment('Fecha y hora de fin de la sesión (null si está en progreso)');

      // Horas trabajadas en esta sesión
      $table->decimal('hours_worked', 8, 2)->nullable()->comment('Horas trabajadas en esta sesión (calculadas al finalizar)');

      // Estado de la sesión
      $table->enum('status', ['in_progress', 'paused', 'completed'])->default('in_progress')
        ->comment('Estado: en progreso, pausado, completado');

      // Motivo de pausa (opcional)
      $table->text('pause_reason')->nullable()->comment('Razón de la pausa si aplica');

      // Notas adicionales
      $table->text('notes')->nullable()->comment('Notas adicionales de la sesión');

      $table->timestamps();
      $table->softDeletes();

      // Índices para mejorar el rendimiento
      $table->index('work_order_planning_id');
      $table->index('status');
      $table->index('start_datetime');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('work_order_planning_sessions');
  }
};

