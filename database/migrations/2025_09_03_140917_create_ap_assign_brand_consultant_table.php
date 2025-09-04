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
    Schema::create('ap_assign_brand_consultant', function (Blueprint $table) {
      $table->id();
      $table->integer('objetivo_venta')->default(0);
      $table->integer('anio');
      $table->integer('month');
      $table->boolean('status')->default(true);
      $table->foreignId('marca_id')
        ->constrained('ap_vehicle_brand');
      $table->integer('asesor_id');
      $table->foreign('asesor_id')->references('id')->on('rrhh_persona');
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');
      $table->unique(['marca_id', 'asesor_id', 'sede_id', 'anio', 'month'], 'unique_consultant_sede_anio_mes');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_assign_brand_consultant');
  }
};
