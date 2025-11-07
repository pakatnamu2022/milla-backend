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
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->string('code_dynamics', 50)->nullable()->after('account');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->dropColumn('code_dynamics');
    });
  }
};
