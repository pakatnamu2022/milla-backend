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
    Schema::table('gh_per_diem_expense', function (Blueprint $table) {
      $table->string('ruc', 20)->nullable()->after('receipt_number');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_expense', function (Blueprint $table) {
      $table->dropColumn('ruc');
    });
  }
};
