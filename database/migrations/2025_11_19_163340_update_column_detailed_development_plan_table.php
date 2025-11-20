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
    Schema::table('detailed_development_plan', function (Blueprint $table) {
      $table->string('title', 255)->after('description');
      $table->date('start_date');
      $table->date('end_date');
      $table->dropForeign(['gh_evaluation_id']);
      $table->dropColumn('gh_evaluation_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('detailed_development_plan', function (Blueprint $table) {
      $table->foreignId('gh_evaluation_id')->constrained('gh_evaluation')->onDelete('cascade');
      $table->dropColumn(['title', 'start_date', 'end_date']);
    });
  }
};
