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
    Schema::create('audit_logs', function (Blueprint $table) {
      $table->integer('user_id')->nullable();
      $table->string('user_name')->nullable();
      $table->string('user_email')->nullable();
      $table->string('auditable_type');
      $table->unsignedBigInteger('auditable_id');
      $table->enum('action', ['created', 'updated', 'deleted', 'viewed', 'restored']);
      $table->json('old_values')->nullable();
      $table->json('changed_fields')->nullable();
      $table->json('new_values')->nullable();
      $table->string('ip_address')->nullable();
      $table->text('user_agent')->nullable();
      $table->string('url')->nullable();
      $table->string('method', 10)->nullable();
      $table->string('request_data')->nullable();
      $table->string('description')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamp('created_at')->useCurrent();
      $table->index(['auditable_type', 'auditable_id']);
      $table->index(['user_id', 'created_at']);
      $table->index(['action', 'created_at']);
      $table->index('created_at');
      $table->foreign('user_id')->references('id')->on('usr_users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('audit_logs');
  }
};
