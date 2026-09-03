<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * Agrega campos para trackear Notas de Crédito y re-reserva de stock:
   * - had_credit_note: TRUE si se generó NC para esta OT/Cotización
   * - stock_re_reserved: TRUE si ya se ejecutó manualmente la re-reserva de stock
   *
   * CONTEXTO:
   * Cuando se genera una NC, el stock regresa a quantity pero NO a reserved_quantity.
   * Si vuelven a facturar la misma OT/Cotización, necesitan re-reservar manualmente.
   * Estos campos permiten validar y alertar sobre este caso.
   */
  public function up(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->boolean('had_credit_note')->default(false)
        ->after('output_generation_warehouse')
        ->comment('TRUE si se generó nota de crédito para esta cotización');

      $table->boolean('stock_re_reserved')->default(false)
        ->after('had_credit_note')
        ->comment('TRUE si se ejecutó manualmente la re-reserva de stock después de NC');
    });

    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->boolean('had_credit_note')->default(false)
        ->after('output_generation_warehouse')
        ->comment('TRUE si se generó nota de crédito para esta orden de trabajo');

      $table->boolean('stock_re_reserved')->default(false)
        ->after('had_credit_note')
        ->comment('TRUE si se ejecutó manualmente la re-reserva de stock después de NC');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropColumn(['had_credit_note', 'stock_re_reserved']);
    });

    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropColumn(['had_credit_note', 'stock_re_reserved']);
    });
  }
};
