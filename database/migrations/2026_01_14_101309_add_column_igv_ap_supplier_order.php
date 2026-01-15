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
      $table->decimal('net_amount', 15, 4)->default(0)->after('supply_type')->comment('Total neto de la orden de proveedor');
      $table->decimal('tax_amount', 12, 4)->default(0)->after('net_amount')->comment('Total IGV de la orden de proveedor');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->dropColumn('net_amount');
      $table->dropColumn('tax_amount');
    });
  }
};
