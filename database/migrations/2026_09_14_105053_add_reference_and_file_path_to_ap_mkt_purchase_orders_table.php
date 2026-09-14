<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * `reference`: N° de referencia del proveedor para la OC (MIGO / PLANKET según marca).
   * `file_path`: PDF de la orden de compra adjuntado al crearla/editarla.
   */
  public function up(): void
  {
    Schema::table('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->string('reference', 100)->nullable()->after('number');
      $table->string('file_path', 500)->nullable()->after('notes');
    });
  }

  public function down(): void
  {
    Schema::table('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->dropColumn(['reference', 'file_path']);
    });
  }
};
