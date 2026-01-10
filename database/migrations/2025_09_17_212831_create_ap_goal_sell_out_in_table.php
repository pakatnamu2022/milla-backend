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
    Schema::create('ap_goal_sell_out_in', function (Blueprint $table) {
      $table->id();
      $table->integer('year');
      $table->integer('month');
      $table->integer('goal');
      $table->enum('type', ['IN', 'OUT']);
      $table->foreignId('brand_id')
        ->constrained('ap_vehicle_brand');
      $table->foreignId('shop_id')
        ->constrained('ap_masters');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_goal_sell_out_in');
  }
};
