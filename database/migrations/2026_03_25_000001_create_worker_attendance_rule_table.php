<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('worker_attendance_rule', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id');
      $table->string('attendance_rule_code', 10);
      $table->timestamps();

      $table->index('worker_id');
      $table->unique(['worker_id', 'attendance_rule_code']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('worker_attendance_rule');
  }
};
