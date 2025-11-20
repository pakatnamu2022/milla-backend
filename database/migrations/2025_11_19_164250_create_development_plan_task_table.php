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
    Schema::create('development_plan_task', function (Blueprint $table) {
      $table->id();
      $table->text('description');
      $table->date('end_date');
      $table->boolean('fulfilled')->default(false);
      $table->foreignId('detailed_development_plan_id')
        ->constrained('detailed_development_plan')
        ->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('development_plan_task');
  }
};
