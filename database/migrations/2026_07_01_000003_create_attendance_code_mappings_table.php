<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('attendance_code_mappings', function (Blueprint $table) {
      $table->id();
      $table->string('emp_code', 50)->unique();
      $table->string('vat', 20);
      $table->string('note', 500)->nullable();
      $table->integer('created_by')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('attendance_code_mappings');
  }
};
