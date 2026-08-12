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
    Schema::create('type_planning_concept_objective_period_pv', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('type_planning_id');
      $table->unsignedBigInteger('concept_objective_period_pv_id');
      $table->timestamps();

      $table->foreign('type_planning_id', 'fk_tp_cop_tp_id')->references('id')->on('type_planning_work_order')->onDelete('cascade');
      $table->foreign('concept_objective_period_pv_id', 'fk_tp_cop_cop_id')->references('id')->on('concept_objective_period_pv')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('type_planning_concept_objective_period_pv');
  }
};