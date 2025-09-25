<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evaluation_person_dashboards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedInteger('person_id');

            // Total Progress
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->integer('completed_sections')->default(0);
            $table->integer('total_sections')->default(0);
            $table->boolean('is_completed')->default(false);

            // Objectives Progress
            $table->decimal('objectives_completion_rate', 5, 2)->default(0);
            $table->integer('objectives_completed')->default(0);
            $table->integer('objectives_total')->default(0);
            $table->boolean('objectives_is_completed')->default(false);
            $table->boolean('has_objectives')->default(false);

            // Competences Progress
            $table->decimal('competences_completion_rate', 5, 2)->default(0);
            $table->integer('competences_completed')->default(0);
            $table->integer('competences_total')->default(0);
            $table->boolean('competences_is_completed')->default(false);
            $table->integer('competence_groups')->default(0);

            // Status
            $table->enum('progress_status', ['sin_iniciar', 'en_proceso', 'completado'])->default('sin_iniciar');

            // JSON fields for complex data
            $table->json('grouped_competences')->nullable();
            $table->json('total_progress_detail')->nullable();
            $table->json('objectives_progress_detail')->nullable();
            $table->json('competences_progress_detail')->nullable();

            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('evaluation_id')->references('id')->on('gh_evaluation')->onDelete('cascade');

            $table->unique(['evaluation_id', 'person_id']);
            $table->index(['evaluation_id', 'person_id']);
            $table->index('progress_status');
            $table->index('is_completed');
            $table->index('last_calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_person_dashboards');
    }
};
