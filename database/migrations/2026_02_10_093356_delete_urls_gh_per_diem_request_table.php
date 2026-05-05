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
      $table->dropColumn('deposit_voucher_url');
      $table->dropColumn('deposit_voucher_url_2');
      $table->dropColumn('deposit_voucher_url_3');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->string('deposit_voucher_url')->nullable()->after('with_request');
      $table->string('deposit_voucher_url_2')->nullable()->after('deposit_voucher_url');
      $table->string('deposit_voucher_url_3')->nullable()->after('deposit_voucher_url_2');
    });
  }
};
