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
    Schema::create('vehicle_leakage_jobs', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id')->nullable();
      $table->foreign('user_id')->references('id')->on('usr_users')
        ->onDelete('set null');
      $table->string('original_filename');
      $table->string('temp_file_path');
      $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
      $table->string('processed_file_path')->nullable();
      $table->json('results')->nullable();
      $table->text('error_message')->nullable();
      $table->timestamp('started_at')->nullable();
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();

      $table->index('status');
      $table->index('user_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('vehicle_leakage_jobs');
  }
};
