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
    Schema::table('type_planning_work_order', function (Blueprint $table) {
      $table->enum('type_document', ['INTERNA_SC', 'INTERNA_CC', 'PAYMENT_RECEIPTS'])->comment("INTERNA_SC = interna sin comprobante, INTERNA_SC = interna con comprobante y PAYMENT_RECEIPTS = comprobante de pago")->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('type_planning_work_order', function (Blueprint $table) {
      $table->enum('type_document', ['INTERNA', 'PAYMENT_RECEIPTS'])->comment("INTERNA = interna y PAYMENT_RECEIPTS = comprobante de pago")->change();
    });
  }
};
