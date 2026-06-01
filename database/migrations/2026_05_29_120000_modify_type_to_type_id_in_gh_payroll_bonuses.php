<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('gh_payroll_bonuses', function (Blueprint $table) {
      $table->dropColumn('type');
    });

    Schema::table('gh_payroll_bonuses', function (Blueprint $table) {
      $table->unsignedBigInteger('type_id')->after('amount');
      $table->foreign('type_id')->references('id')->on('gp_masters');
    });
  }

  public function down(): void
  {
    Schema::table('gh_payroll_bonuses', function (Blueprint $table) {
      $table->dropForeign(['type_id']);
      $table->dropColumn('type_id');
    });

    Schema::table('gh_payroll_bonuses', function (Blueprint $table) {
      $table->string('type', 100)->after('amount');
    });
  }
};
