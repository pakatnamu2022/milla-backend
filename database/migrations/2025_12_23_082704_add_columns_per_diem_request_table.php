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
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->boolean('with_active')->default(false)->after('notes')->comment('Indica si viajara en un activo de la empresa');
      $table->boolean('with_request')->default(false)->after('with_active')->comment('Indica si solicitara presupuesto o rendira gastos');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->dropColumn('with_active');
      $table->dropColumn('with_request');
    });
  }
};
