<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('gh_payroll_loan_extra_discounts', function (Blueprint $table) {
      $table->integer('confirmed_by')->nullable()->after('applied');
      $table->dateTime('confirmed_at')->nullable()->after('confirmed_by');

      $table->foreign('confirmed_by')->references('id')->on('usr_users')->onDelete('set null');
    });
  }

  public function down(): void
  {
    Schema::table('gh_payroll_loan_extra_discounts', function (Blueprint $table) {
      $table->dropForeign(['confirmed_by']);
      $table->dropColumn(['confirmed_by', 'confirmed_at']);
    });
  }
};
