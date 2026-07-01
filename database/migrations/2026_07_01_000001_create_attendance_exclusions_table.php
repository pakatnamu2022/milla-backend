<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('attendance_exclusions', function (Blueprint $table) {
      $table->id();
      $table->integer('person_id');
      $table->string('reason', 500)->nullable();
      $table->boolean('active')->default(true);
      $table->integer('created_by')->nullable();
      $table->timestamps();

      $table->foreign('person_id')->references('id')->on('rrhh_persona');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('attendance_exclusions');
  }
};