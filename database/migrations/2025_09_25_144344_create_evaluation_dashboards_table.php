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
        Schema::create('evaluation_dashboards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_id')->unique();

            // Progress Stats
            $table->integer('total_participants')->default(0);
            $table->integer('completed_participants')->default(0);
            $table->integer('in_progress_participants')->default(0);
            $table->integer('not_started_participants')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);

            // Executive Summary Stats
            $table->decimal('average_final_score', 8, 2)->default(0);
            $table->decimal('performance_percentage', 5, 2)->default(0);

            // JSON fields for complex data
            $table->json('competence_stats')->nullable();
            $table->json('evaluator_type_stats')->nullable();
            $table->json('participant_ranking')->nullable();
            $table->json('executive_summary')->nullable();

            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('evaluation_id')->references('id')->on('gh_evaluation')->onDelete('cascade');
            $table->index('evaluation_id');
            $table->index('last_calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_dashboards');
    }
};
