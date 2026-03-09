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
    Schema::table('gh_payroll_periods', function (Blueprint $table) {
      // Remove unique constraint from code column
      $table->dropUnique('gh_payroll_periods_code_unique');

      // Remove unique constraint from year, month, company_id
      $table->dropUnique('unique_period_company');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_payroll_periods', function (Blueprint $table) {
      // Restore unique constraint on code column
      $table->unique('code', 'gh_payroll_periods_code_unique');

      // Restore unique constraint on year, month, company_id
      $table->unique(['year', 'month', 'company_id'], 'unique_period_company');
    });
  }
};
