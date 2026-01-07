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
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->boolean('has_invoice_generated')->default(false)->after('is_take')->comment('Indica si ya se generó un comprobante para esta cotización');
      $table->boolean('is_fully_paid')->default(false)->after('has_invoice_generated')->comment('Indica si la cotización ya fue cancelada en su totalidad');
      $table->boolean('output_generation_warehouse')->default(false)->after('is_fully_paid')->comment('Indica si se generó la salida de almacén para esta cotización');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropColumn(['has_invoice_generated', 'is_fully_paid', 'output_generation_warehouse']);
    });
  }
};

