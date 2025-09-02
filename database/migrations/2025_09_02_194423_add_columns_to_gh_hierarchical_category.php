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
    Schema::table('gh_hierarchical_category', function (Blueprint $table) {
      $table->boolean('hasObjectives')->default(true)->after('description');
      $table->decimal('objectivePercentage')->default(0)->after('hasObjectives');
      $table->decimal('competencePercentage')->default(0)->after('objectivePercentage');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_hierarchical_category', function (Blueprint $table) {
      $table->dropColumn(['hasObjectives', 'objectivePercentage', 'competencePercentage']);
    });
  }
};
