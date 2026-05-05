<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('attendance_rules', function (Blueprint $table) {
      $table->id();
      $table->string('code', 10);
      $table->string('hour_type', 50);
      $table->decimal('hours', 5)->nullable();
      $table->decimal('multiplier', 10, 4)->default(1);
      $table->boolean('pay')->default(true);
      $table->boolean('use_shift')->default(false);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('attendance_rules');
  }
};
