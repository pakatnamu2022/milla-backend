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
    Schema::table('ap_supplier_order_details', function (Blueprint $table) {
      $table->decimal('unit_price', 15)->change();
      $table->decimal('quantity', 15)->change();
      $table->decimal('total', 15)->change();
    });

    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->decimal('net_amount', 15)->change();
      $table->decimal('tax_amount', 15)->change();
      $table->decimal('total_amount', 15)->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_supplier_order_details', function (Blueprint $table) {
      $table->decimal('unit_price', 15, 4)->change();
      $table->decimal('quantity', 15, 4)->change();
      $table->decimal('total', 15, 4)->change();
    });

    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->decimal('net_amount', 15, 4)->change();
      $table->decimal('tax_amount', 15, 4)->change();
      $table->decimal('total_amount', 15, 4)->change();
    });
  }
};
