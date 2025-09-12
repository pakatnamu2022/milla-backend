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
    Schema::create('evaluation_person_competence_detail', function (Blueprint $table) {
      $table->id();

      $table->foreignId('evaluation_id')->constrained('gh_evaluation');

      $table->integer('person_id');
      $table->foreign('person_id')->references('id')->on('rrhh_persona');

      $table->integer('competence_id');
      $table->foreign('competence_id')->references('id')->on('gh_config_competencias');

      $table->integer('sub_competence_id');
      $table->foreign('sub_competence_id')->references('id')->on('gh_config_subcompetencias');

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
    Schema::dropIfExists('evaluation_person_competence_detail');
  }
};
