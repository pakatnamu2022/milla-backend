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
    Schema::table('gh_evaluation_person_competence_detail', function (Blueprint $table) {
      $table->integer('evaluator_id')->after('person_id'); // mismo tipo que rrhh_persona.id
      $table->foreign('evaluator_id')->references('id')->on('rrhh_persona');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_person_competence_detail', function (Blueprint $table) {
      $table->dropForeign(['evaluator_id']);
      $table->dropColumn('evaluator_id');
    });
  }
};
