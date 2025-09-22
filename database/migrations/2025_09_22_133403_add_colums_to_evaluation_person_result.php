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
    Schema::table('gh_evaluation_person_result', function (Blueprint $table) {
      $table->string('name');
      $table->string('dni');
      $table->string('hierarchical_category');
      $table->string('position');
      $table->string('area');
      $table->string('sede');
      $table->string('boss');
      $table->string('boss_dni');
      $table->string('boss_hierarchical_category');
      $table->string('boss_position');
      $table->string('boss_area');
      $table->string('boss_sede');
      $table->string('comments')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_person_result', function (Blueprint $table) {
      //
    });
  }
};
