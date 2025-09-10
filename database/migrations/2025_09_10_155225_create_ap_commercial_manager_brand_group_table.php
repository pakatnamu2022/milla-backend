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
    Schema::create('ap_commercial_manager_brand_group', function (Blueprint $table) {
      $table->id();
      $table->integer('commercial_manager_id');
      $table->foreignId('brand_group_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreign('commercial_manager_id')
        ->references('id')
        ->on('rrhh_persona')
        ->onDelete('cascade')
        ->onUpdate('cascade');
      $table->timestamps();
      $table->softDeletes();
      $table->unique(['commercial_manager_id', 'brand_group_id'], 'unique_commercial_manager_brand_group');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_commercial_manager_brand_group');
  }
};
