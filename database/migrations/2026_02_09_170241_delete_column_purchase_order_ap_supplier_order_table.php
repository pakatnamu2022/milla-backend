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
      $table->dropForeign(['ap_purchase_order_id']);
      $table->dropColumn('ap_purchase_order_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->foreignId('ap_purchase_order_id')->nullable()->constrained('ap_purchase_order')->onUpdate('cascade')->onDelete('set null');
    });
  }
};
