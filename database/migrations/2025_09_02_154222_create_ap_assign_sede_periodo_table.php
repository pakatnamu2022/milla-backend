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
    Schema::create('ap_assign_sede_periodo', function (Blueprint $table) {
      $table->id();
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');
      $table->integer('asesor_id');
      $table->foreign('asesor_id')->references('id')->on('rrhh_persona');
      $table->integer('anio');
      $table->integer('mes');
      $table->unique(['sede_id', 'asesor_id', 'anio', 'mes']);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_assign_sede_periodo');
  }
};
