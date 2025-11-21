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
      $table->dropColumn('supplier_invoice_number');
      $table->dropColumn('supplier_invoice_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('purchase_receptions', function (Blueprint $table) {
      $table->string('supplier_invoice_number')->nullable()->after('warehouse_id')->comment('Número de factura del proveedor');
      $table->date('supplier_invoice_date')->nullable()->after('supplier_invoice_number')->comment('Fecha de la factura del proveedor');
    });
  }
};
