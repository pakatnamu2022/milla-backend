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
    Schema::create('phone_line_worker', function (Blueprint $table) {
      $table->id();
      $table->foreignId('phone_line_id')->constrained('phone_line')->cascadeOnDelete()->comment('Línea telefónica');
      $table->integer('worker_id')->comment('ID del trabajador asignado');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona')->cascadeOnDelete();
      $table->timestamp('assigned_at')->nullable()->comment('Fecha de asignación');
      $table->timestamps();
      $table->softDeletes();

      // Unique constraint para evitar duplicados
      $table->unique(['phone_line_id', 'worker_id', 'deleted_at'], 'unique_phone_line_worker');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('phone_line_worker');
  }
};
