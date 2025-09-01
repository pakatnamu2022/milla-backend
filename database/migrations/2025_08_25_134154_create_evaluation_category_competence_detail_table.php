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
    Schema::create('gh_evaluation_category_competence', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('competence_id');
      $table->foreign('competence_id', 'fk_evaluation_competence_det')
        ->references('id')->on('gh_config_competencias')
        ->onUpdate('cascade')->onDelete('cascade');

      $table->foreignId('category_id')->constrained('gh_hierarchical_category', 'id', 'fk_hierarchical_category_comp_det');

      $table->integer('person_id'); // mismo tipo que rrhh_persona.id
      $table->foreign('person_id')->references('id')->on('rrhh_persona');

      $table->boolean('active')->default(1);

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_evaluation_category_competence');
  }
};
