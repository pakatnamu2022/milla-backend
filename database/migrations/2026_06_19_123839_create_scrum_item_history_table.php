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
    Schema::create('scrum_item_history', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_id')->constrained('scrum_items')->cascadeOnDelete();


      $table->integer('user_id');
      $table->foreign('user_id')->references('id')->on('usr_users')->cascadeOnDelete();

      $table->string('field');
      $table->text('old_value')->nullable();
      $table->text('new_value')->nullable();
      $table->timestamp('created_at')->useCurrent();

      $table->index(['item_id', 'created_at']);
      $table->index('user_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('scrum_item_history');
  }
};
