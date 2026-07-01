<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_models_vn_sync_logs', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('model_vn_id');
      $table->string('code', 50)->nullable();
      $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
      $table->tinyInteger('proceso_estado')->default(0);
      $table->json('dynamics_payload')->nullable();
      $table->text('error_message')->nullable();
      $table->unsignedTinyInteger('attempts')->default(0);
      $table->timestamp('last_attempt_at')->nullable();
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();

      $table->foreign('model_vn_id')->references('id')->on('ap_models_vn')->onDelete('cascade');
      $table->index('status');
      $table->index(['model_vn_id', 'status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_models_vn_sync_logs');
  }
};
