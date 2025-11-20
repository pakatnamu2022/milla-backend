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
    Schema::create('development_plan_objective_competence', function (Blueprint $table) {
      $table->id();
      $table->foreignId('development_plan_id')->constrained('detailed_development_plan', 'id', 'development_plan_fk')->onDelete('cascade');
      $table->foreignId('objective_detail_id')->nullable()->constrained('gh_evaluation_person_cycle_detail', 'id', 'objective_detail_fk')->onDelete('cascade');
      $table->foreignId('competence_detail_id')->nullable()->constrained('gh_evaluation_person_competence_detail', 'id', 'competence_detail_fk')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('development_plan_objective_competence');
  }
};
