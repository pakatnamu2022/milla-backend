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
      $table->string('reason', 500)->nullable()->after('rejection_reason')->comment('Motivo para generar planilla de movilidad');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_expense', function (Blueprint $table) {
      $table->dropColumn('reason');
    });
  }
};
