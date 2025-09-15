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
    Schema::create('gh_evaluation_person_competence_detail', function (Blueprint $table) {
      $table->id();

      $table->foreignId('evaluation_id')->constrained('gh_evaluation', 'id');

      $table->integer('person_id');
      $table->foreign('person_id')->references('id')->on('rrhh_persona');

      $table->foreignId('competence_id')->constrained('gh_config_competencias');
      $table->foreignId('sub_competence_id')->constrained('gh_config_subcompetencias');

      $table->text('person');
      $table->text('competence');
      $table->text('sub_competence');
      $table->integer('evaluatorType')->default(0)->comment('0: chief, 1: self, 2: partners, 3: reports');
      $table->decimal('result')->default(0);

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_evaluation_person_competence_detail');
  }
};
