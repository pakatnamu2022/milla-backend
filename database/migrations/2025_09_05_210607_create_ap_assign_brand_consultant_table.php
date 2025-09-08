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
      $table->integer('sales_target')->default(0);
      $table->integer('year');
      $table->integer('month');
      $table->boolean('status')->default(true);
      $table->foreignId('brand_id')
        ->constrained('ap_vehicle_brand');
      $table->integer('worker_id');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');
      $table->foreignId('company_branch_id')->constrained('company_branch');
      $table->unique(['brand_id', 'worker_id', 'company_branch_id', 'year', 'month'], 'unique_consultant_sede_anio_mes');
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
