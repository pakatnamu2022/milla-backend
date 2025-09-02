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
    Schema::table('gh_evaluation_objective', function (Blueprint $table) {
      $table->boolean('isAscending')->default(true)->after('fixedWeight');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_objective', function (Blueprint $table) {
      $table->dropColumn('isAscending');
    });
  }
};
