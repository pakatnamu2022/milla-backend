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
    Schema::create('scrum_projects', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->text('description')->nullable();
      $table->string('color')->default('#3B82F6');
      $table->enum('status', ['activo', 'archivado'])->default('activo');
      $table->integer('created_by');
      $table->foreign('created_by')->references('id')->on('usr_users')->cascadeOnDelete();
      $table->timestamps();

      $table->index('status');
      $table->index('created_by');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('scrum_projects');
  }
};
