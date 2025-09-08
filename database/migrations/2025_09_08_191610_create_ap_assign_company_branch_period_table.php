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
    Schema::create('ap_assign_company_branch_period', function (Blueprint $table) {
      $table->id();
      $table->foreignId('company_branch_id')->constrained('company_branch');
      $table->integer('worker_id');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');
      $table->integer('year');
      $table->integer('month');
      $table->unique(['company_branch_id', 'worker_id', 'year', 'month'], 'uniq_sede_periodo');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_assign_company_branch_period');
  }
};
