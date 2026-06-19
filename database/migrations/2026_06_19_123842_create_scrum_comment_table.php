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
    Schema::create('scrum_comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_id')->constrained('scrum_items')->cascadeOnDelete();
      $table->integer('user_id')->nullable();
      $table->foreign('user_id')->references('id')->on('usr_users')->cascadeOnDelete();
      $table->text('content');
      $table->timestamps();

      $table->index('item_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('scrum_comments');
  }
};
