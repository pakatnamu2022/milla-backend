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
    Schema::create('gh_evaluation_person', function (Blueprint $table) {
      $table->id();

      $table->integer('person_id');
      $table->foreign('person_id')->references('id')->on('rrhh_persona');

      $table->integer('chief_id');
      $table->foreign('chief_id')->references('id')->on('rrhh_persona');

      $table->foreignId('person_cycle_detail_id')->constrained('gh_evaluation_person_cycle_detail')->onDelete('cascade');

      $table->text('chief');
      $table->foreignId('evaluation_id')->constrained('gh_evaluation')->onDelete('cascade');
      $table->decimal('result')->default(0);
      $table->decimal('compliance')->default(0);
      $table->decimal('qualification')->default(0);
      $table->text('comment')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_evaluation_person');
  }
};
