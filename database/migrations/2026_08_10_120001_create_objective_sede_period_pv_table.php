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
    Schema::create('objective_sede_period_pv', function (Blueprint $table) {
      $table->id();
      $table->integer('sede_id');
      $table->year('year');
      $table->unsignedTinyInteger('month');
      $table->decimal('amount', 15, 2);
      $table->timestamps();

      $table->foreign('sede_id')->references('id')->on('config_sede')->onDelete('cascade');
      $table->unique(['sede_id', 'year', 'month']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('objective_sede_period_pv');
  }
};
