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
    Schema::create('evaluation_par_evaluator', function (Blueprint $table) {
      $table->id();

      $table->integer('worker_id'); // mismo tipo que rrhh_persona.id
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');

      $table->integer('mate_id'); // mismo tipo que rrhh_persona.id
      $table->foreign('mate_id')->references('id')->on('rrhh_persona');

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('evaluation_par_evaluator');
  }
};
