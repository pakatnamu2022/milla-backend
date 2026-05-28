<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('accounts_receivable_comments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('accounts_receivable_id');
      $table->foreign('accounts_receivable_id')->references('id')->on('accounts_receivable')->cascadeOnDelete();
      $table->integer('sede_id')->nullable();
      $table->foreign('sede_id')->references('id')->on('config_sede')->nullOnDelete();
      $table->integer('user_id');
      $table->foreign('user_id')->references('id')->on('usr_users')->restrictOnDelete();
      $table->text('comment');
      $table->timestamps();

      $table->index('accounts_receivable_id');
      $table->index('sede_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('accounts_receivable_comments');
  }
};
