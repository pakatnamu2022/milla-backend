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
    Schema::create('concept_objective_period_pv', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('objective_sede_period_pv_id');
      $table->unsignedBigInteger('area_id')->nullable();
      $table->text('description');
      $table->boolean('is_vehicular_crossing')->default(false);
      $table->boolean('status')->default(true);
      $table->decimal('sub_amount', 15, 2);
      $table->integer('order')->default(0);
      $table->timestamps();

      $table->foreign('objective_sede_period_pv_id')->references('id')->on('objective_sede_period_pv')->onDelete('cascade');
      $table->foreign('area_id')->references('id')->on('ap_masters')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('concept_objective_period_pv');
  }
};
