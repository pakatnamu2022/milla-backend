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
        Schema::create('gh_evaluation_cycle', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date_objectives');
            $table->date('end_date_objectives');
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('period_id')->constrained('gh_evaluation_periods')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('enableObjectiveWeighting');
            $table->boolean('allowObjectiveEditing');
            $table->boolean('requireEvaluationParameters');
            $table->
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_evaluation_cycle');
    }
};
