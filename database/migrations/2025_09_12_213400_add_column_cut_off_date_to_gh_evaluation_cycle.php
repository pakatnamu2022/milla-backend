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
      $table->date('cut_off_date')->after('end_date')->default(now());
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_cycle', function (Blueprint $table) {
      $table->dropColumn('cut_off_date');
    });
  }
};
