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
    Schema::create('work_order_planning', function (Blueprint $table) {
      $table->id();
      $table->string('description', 255);
      //Datos estimados (opcionales para flexibilidad)
      $table->decimal('estimated_hours', 8, 2);
      $table->dateTime('planned_start_datetime');
      $table->dateTime('planned_end_datetime');
      //Datos reales
      $table->decimal('actual_hours', 8, 2)->nullable();
      $table->dateTime('actual_start_datetime')->nullable();
      $table->dateTime('actual_end_datetime')->nullable();
      //Enum con estado de la planificación
      $table->enum('status', ['planned', 'in_progress', 'completed', 'canceled'])->default('planned');
      //Relaciones
      $table->integer('worker_id');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');
      $table->foreignId('work_order_id')->comment('Orden de trabajo')
        ->constrained('ap_work_orders')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();

      // Índices para mejorar el rendimiento
      $table->index('worker_id');
      $table->index('work_order_id');
      $table->index('status');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('work_order_planning');
  }
};
