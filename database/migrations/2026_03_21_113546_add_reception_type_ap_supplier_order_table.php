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
      $table->enum('reception_type', ['COMPLETE', 'PARTIAL', 'PENDING'])->default('PENDING')->after('exchange_rate')->comment('Tipo de recepción: COMPLETE: Se recibió la orden de compra completa. PARTIAL: Se recibió solo una parte de la orden de compra.');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->dropColumn('reception_type');
    });
  }
};
