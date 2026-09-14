<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('accounts_payable_comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('accounts_payable_id')->constrained('accounts_payable')->cascadeOnDelete();
      $table->integer('user_id')->nullable();
      $table->text('comment');
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('user_id')->references('id')->on('usr_users')->restrictOnDelete();
      $table->index('accounts_payable_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('accounts_payable_comments');
  }
};
