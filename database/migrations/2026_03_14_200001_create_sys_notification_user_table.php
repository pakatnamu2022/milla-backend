<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('sys_notification_user', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->foreignId('notification_id')->constrained('sys_notifications')->cascadeOnDelete();
      $table->integer('user_id');
      $table->foreign('user_id')->references('id')->on('usr_users')->cascadeOnDelete();
      $table->timestamp('read_at')->nullable();
      $table->timestamps();

      $table->unique(['notification_id', 'user_id']);
      $table->index(['user_id', 'read_at']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sys_notification_user');
  }
};
