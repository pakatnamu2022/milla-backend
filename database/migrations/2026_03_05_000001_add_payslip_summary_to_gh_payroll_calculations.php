<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      // Fix: days_worked was not being set during generation
      // (already exists, just ensure it's properly used)

      // Payslip summary columns (boleta de pago)
      $table->decimal('basic_salary', 12, 2)->default(0)->after('days_absent')->comment('REM. BASICA = salary/30 * days_worked');
      $table->decimal('night_bonus', 12, 2)->default(0)->after('basic_salary')->comment('BONIF. NOCT = gross_salary - basic_salary');
      $table->decimal('overtime_25', 12, 2)->default(0)->after('night_bonus')->comment('HE 25% = sum(EARNING where multiplier=1.25)');
      $table->decimal('overtime_35', 12, 2)->default(0)->after('overtime_25')->comment('HE 35% = sum(EARNING where multiplier=1.35)');
      $table->decimal('holiday_pay', 12, 2)->default(0)->after('overtime_35')->comment('FERIADO = sum(FNT+FDT amounts) * 2');
      $table->decimal('compensatory_pay', 12, 2)->default(0)->after('holiday_pay')->comment('DDT = sum(DNT+DDT amounts) * 2');
    });
  }

  public function down(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      $table->dropColumn([
        'basic_salary',
        'night_bonus',
        'overtime_25',
        'overtime_35',
        'holiday_pay',
        'compensatory_pay',
      ]);
    });
  }
};