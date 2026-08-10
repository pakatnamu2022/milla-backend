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
    Schema::create('objective_advisors_period_pv', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id');
      $table->decimal('amount', 15, 2);
      $table->unsignedBigInteger('concept_objective_period_pv_id');
      $table->timestamps();

      $table->foreign('worker_id', 'fk_oa_period_worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
      $table->foreign('concept_objective_period_pv_id', 'fk_oa_period_cop_id')->references('id')->on('concept_objective_period_pv')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('objective_advisors_period_pv');
  }
};
