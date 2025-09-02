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
    Schema::table('gh_evaluation_cycle', function (Blueprint $table) {
      $table->integer('typeEvaluation')->default(0)->after('status'); // 0: Objetivos, 1: 180 o 360
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_cycle', function (Blueprint $table) {
      $table->dropColumn('typeEvaluation');
    });
  }
};
