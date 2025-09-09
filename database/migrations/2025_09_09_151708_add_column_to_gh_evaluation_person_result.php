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
      $table->integer('status')->default(0)->comment('0: pending, 1: completed, 2: published')->after('evaluation_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_person_result', function (Blueprint $table) {
      $table->dropColumn('status');
    });
  }
};
