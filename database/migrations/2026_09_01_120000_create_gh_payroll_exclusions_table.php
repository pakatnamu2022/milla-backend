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
    Schema::create('gh_payroll_exclusions', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id');
      $table->unsignedBigInteger('period_id');
      // Concepto excluido: 'FAMILY_ALLOWANCE' por ahora; tabla pensada para reusarse con otros
      // conceptos (gratificación, CTS, etc.) a futuro sin necesitar una tabla nueva por cada uno.
      $table->string('concept');
      $table->string('reason')->nullable();
      $table->integer('created_by')->nullable();
      $table->timestamps();

      $table->foreign('worker_id')
        ->references('id')
        ->on('rrhh_persona')
        ->onDelete('cascade');

      $table->foreign('period_id')
        ->references('id')
        ->on('gh_payroll_periods')
        ->onDelete('cascade');

      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->index(['worker_id', 'period_id', 'concept']);

      // Un trabajador no puede tener dos exclusiones del mismo concepto en el mismo periodo
      $table->unique(['worker_id', 'period_id', 'concept'], 'unique_worker_period_concept_exclusion');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_exclusions');
  }
};
