<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log de aumentos de sueldo de trabajadores con contrato INDETERMINADO (a quienes no se les
 * genera un contrato nuevo al subir el sueldo, por lo que rrhh_contrato.sueldo queda
 * desactualizado). Reemplaza la edición directa de rrhh_persona.sueldo: cada aumento queda
 * registrado con su fecha efectiva, y WorkerContract::salaryForWorkerAtDate() lo usa para
 * resolver el sueldo vigente en cualquier fecha (planillas de periodos pasados, pólizas, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_salary_increases', function (Blueprint $table) {
            $table->id();
            $table->integer('worker_id');
            $table->decimal('previous_salary', 10, 2);
            $table->decimal('new_salary', 10, 2);
            $table->date('effective_date');
            $table->string('reason')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('set null');

            $table->index(['worker_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_salary_increases');
    }
};
