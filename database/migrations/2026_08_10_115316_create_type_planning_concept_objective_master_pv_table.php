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
    Schema::create('type_planning_concept_objective_master_pv', function (Blueprint $table) {
      $table->unsignedBigInteger('concept_objective_master_pv_id');
      $table->unsignedBigInteger('type_planning_work_order_id');
      $table->timestamps();

      $table->primary(['concept_objective_master_pv_id', 'type_planning_work_order_id'], 'pk_concept_type_planning');
      $table->foreign('concept_objective_master_pv_id', 'fk_concept_objective')
        ->references('id')->on('concept_objective_master_pv')->onDelete('cascade');
      $table->foreign('type_planning_work_order_id', 'fk_type_planning_wo')
        ->references('id')->on('type_planning_work_order')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('type_planning_concept_objective_master_pv');
  }
};
