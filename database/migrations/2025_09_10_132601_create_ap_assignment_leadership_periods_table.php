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
    Schema::create('ap_assignment_leadership_periods', function (Blueprint $table) {
      $table->id();
      $table->integer('boss_id');
      $table->integer('worker_id');
      $table->integer('year');
      $table->integer('month');
      $table->foreign('boss_id')
        ->references('id')
        ->on('rrhh_persona');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');
      $table->unique(['boss_id', 'worker_id', 'year', 'month'], 'uniq_boss_worker_period');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_assignment_leadership_periods');
  }
};
