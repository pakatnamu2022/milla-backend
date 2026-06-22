<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('manuals', function (Blueprint $table) {
      $table->id();
      $table->integer('vista_id');
      $table->string('company_slug');
      $table->string('module_slug');
      $table->string('title');
      $table->text('description')->nullable();
      $table->unsignedInteger('order')->default(0);
      $table->timestamps();

      $table->foreign('vista_id')->references('id')->on('config_vista')->onDelete('cascade');
      $table->foreignId('digital_file_id')->references('id')->on('gp_digital_files')->onDelete('cascade');
      $table->index('vista_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('manuals');
  }
};
