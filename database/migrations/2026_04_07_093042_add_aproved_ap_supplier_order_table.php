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
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->integer('approved_by')->nullable()->after('type_currency_id');
      $table->foreign('approved_by')->references('id')->on('usr_users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->dropForeign(['approved_by']);
      $table->dropColumn('approved_by');
    });
  }
};
