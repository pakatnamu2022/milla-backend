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
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->enum('movement_type', [
        'PURCHASE_RECEPTION',    // Recepción de compra
        'SALE',                  // Venta
        'ADJUSTMENT_IN',         // Ajuste positivo (encontraste stock)
        'ADJUSTMENT_OUT',        // Ajuste negativo (merma, robo)
        'TRANSFER_OUT',          // Transferencia salida (origen)
        'TRANSFER_IN',           // Transferencia entrada (destino)
        'RETURN_IN',             // Devolución de cliente (ingreso)
        'RETURN_OUT',            // Devolución a proveedor (salida)
      ])->change()->comment('Type of inventory movement');
      $table->foreignId('reason_in_out_id')->nullable()->after('warehouse_destination_id')
        ->constrained('ap_masters')->comment('Reason for adjustment in/out');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->enum('movement_type', [
        'PURCHASE_RECEPTION',
        'SALE',
        'ADJUSTMENT_IN',
        'ADJUSTMENT_OUT',
        'TRANSFER_OUT',
        'TRANSFER_IN',
        'RETURN_IN',
        'RETURN_OUT',
        'LOSS',
        'DAMAGE',
      ])->change()->comment('Type of inventory movement');
      $table->dropForeign(['reason_in_out_id']);
      $table->dropColumn('reason_in_out_id');
    });
  }
};
