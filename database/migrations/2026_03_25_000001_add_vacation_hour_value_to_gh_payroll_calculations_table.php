<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      $table->decimal('vacation_hour_value', 10, 2)
        ->default(0)
        ->after('base_hour_value')
        ->comment('Vacation hour value snapshot');
    });
  }

  public function down(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      $table->dropColumn('vacation_hour_value');
    });
  }
};

