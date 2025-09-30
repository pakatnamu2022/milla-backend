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
    Schema::create('opportunity_action', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id'); // mismo tipo que rrhh_persona.id
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');


      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('opportunity_action');
  }
};
