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
      $table->foreignId('hierarchical_category_id')->default(1)->constrained('gh_hierarchical_category')->cascadeOnDelete();
      $table->boolean('hasObjectives')->default(false);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_person_result', function (Blueprint $table) {
      $table->dropForeign(['hierarchical_category_id']);
      $table->dropColumn('hierarchical_category_id');
      $table->dropColumn('hasObjectives');
    });
  }
};
