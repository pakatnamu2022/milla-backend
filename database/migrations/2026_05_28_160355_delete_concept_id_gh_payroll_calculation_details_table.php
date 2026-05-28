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
    Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
      $table->dropForeign(['concept_id']);
      $table->dropColumn('concept_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
      $table->unsignedBigInteger('concept_id')->nullable()->after('calculation_id');
      $table->foreign('concept_id')->references('id')->on('gh_payroll_concepts')->onDelete('set null');
    });
  }
};
