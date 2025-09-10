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
    Schema::create('ap_assignment_leadership', function (Blueprint $table) {
      $table->id();
      $table->integer('boss_id');
      $table->integer('worker_id');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');
      $table->foreign('boss_id')
        ->references('id')
        ->on('rrhh_persona')
        ->onDelete('cascade')
        ->onUpdate('cascade');
      $table->timestamps();
      $table->softDeletes();
      $table->unique(['boss_id', 'worker_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_assignment_leadership');
  }
};
