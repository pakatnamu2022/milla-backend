<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('gh_payroll_periods', function (Blueprint $table) {
      $table->date('biweekly_date')->nullable()->after('payment_date')->comment('Fecha de pago quincenal (primer grupo hasta esta fecha)');
    });
  }

  public function down(): void
  {
    Schema::table('gh_payroll_periods', function (Blueprint $table) {
      $table->dropColumn('biweekly_date');
    });
  }
};
