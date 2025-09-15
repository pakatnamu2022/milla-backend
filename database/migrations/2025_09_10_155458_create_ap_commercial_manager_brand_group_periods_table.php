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
    Schema::create('ap_commercial_manager_brand_group_periods', function (Blueprint $table) {
      $table->id();

      $table->integer('commercial_manager_id');
      $table->unsignedBigInteger('brand_group_id');

      $table->integer('year');
      $table->integer('month');
      $table->boolean('status')->default(true);

      $table->unique(
        ['commercial_manager_id', 'brand_group_id', 'year', 'month'],
        'uniq_mgr_brandgroup_period'
      );

      $table->timestamps();
      $table->softDeletes();

      $table->foreign('commercial_manager_id', 'fk_mgr_persona')
        ->references('id')->on('rrhh_persona')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();

      $table->foreign('brand_group_id', 'fk_mgr_brandgroup')
        ->references('id')->on('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_commercial_manager_brand_group_periods');
  }
};
