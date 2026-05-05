<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      $table->unsignedTinyInteger('biweekly')->nullable()->after('period_id')
        ->comment('1 = first half, 2 = second half, null = full month');
    });
  }

  public function down(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      $table->dropColumn('biweekly');
    });
  }
};
