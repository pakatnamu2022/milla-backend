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
    Schema::create('gh_evaluation_category_competence_detail', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('competence_id');
      $table->foreign('competence_id', 'fk_evaluation_competence_det')
        ->references('id')->on('gh_config_subcompetencias')
        ->onUpdate('cascade')->onDelete('cascade');

      $table->foreignId('category_id')->constrained('gh_hierarchical_category', 'id', 'fk_hierarchical_category_comp_det');

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_evaluation_category_competence_detail');
  }
};
