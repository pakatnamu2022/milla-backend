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
    Schema::create('gh_evaluation_category_objective', function (Blueprint $table) {
      $table->id();

      $table->foreignId('objective_id')->constrained('gh_evaluation_objective', 'id', 'fk_evaluation_objective_det');
      $table->foreignId('category_id')->constrained('gh_hierarchical_category', 'id', 'fk_hierarchical_category_det');

      $table->integer('person_id'); // mismo tipo que rrhh_persona.id
      $table->foreign('person_id')->references('id')->on('rrhh_persona');

      $table->decimal('goal', 10, 2)->nullable()->default(0);
      $table->decimal('weight', 5, 2)->nullable()->default(0);
      $table->boolean('fixedWeight')->default(0);

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
    Schema::dropIfExists('gh_evaluation_category_objective');
  }
};
