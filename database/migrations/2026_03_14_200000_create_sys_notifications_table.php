<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('sys_notifications', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->string('title', 255);
      $table->text('body');
      $table->string('type', 100);
      $table->json('data')->nullable();
      $table->nullableMorphs('notifiable');
      $table->integer('created_by')->nullable();
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('sys_notifications');
  }
};
