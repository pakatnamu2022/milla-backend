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
    Schema::table('purchase_receptions', function (Blueprint $table) {
      $table->foreignId('ap_supplier_order_id')->after('purchase_order_id')->nullable()->constrained('ap_supplier_order')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('purchase_receptions', function (Blueprint $table) {
      $table->dropForeign(['ap_supplier_order_id']);
      $table->dropColumn('ap_supplier_order_id');
    });
  }
};
