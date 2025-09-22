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
    Schema::create('ap_safe_credit_goal', function (Blueprint $table) {
      $table->id();
      $table->integer('year');
      $table->integer('month');
      $table->decimal('goal_amount', 15, 2);
      $table->enum('type', ['CREDITO', 'SEGURO']);
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_safe_credit_goal');
  }
};
