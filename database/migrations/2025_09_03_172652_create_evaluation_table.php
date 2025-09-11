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
    Schema::create('gh_evaluation', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->date('start_date');
      $table->date('end_date');
      $table->integer('status')->default(0)->comment('0: programmed, 1: in progress , 2: finished');
      $table->integer('typeEvaluation')->default(0); // 0: Objetivos, 1: 180 o 360
      $table->decimal('objectivesPercentage')->default(0);
      $table->decimal('competencesPercentage')->default(0);
      $table->foreignId('cycle_id')->constrained('gh_evaluation_cycle');
      $table->foreignId('period_id')->constrained('gh_evaluation_periods');
      $table->foreignId('competence_parameter_id')->constrained('gh_evaluation_parameter');
      $table->foreignId('objective_parameter_id')->constrained('gh_evaluation_parameter');
      $table->foreignId('final_parameter_id')->constrained('gh_evaluation_parameter');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_evaluation');
  }
};
