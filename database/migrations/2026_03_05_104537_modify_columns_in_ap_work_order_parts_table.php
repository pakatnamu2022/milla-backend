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
    Schema::table('ap_work_order_parts', function (Blueprint $table) {
      // Eliminar columna unit_cost
      $table->dropColumn('unit_cost');

      // Renombrar subtotal a total_cost
      $table->renameColumn('subtotal', 'total_cost');

      // Renombrar total_amount a net_amount
      $table->renameColumn('total_amount', 'net_amount');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_order_parts', function (Blueprint $table) {
      // Renombrar net_amount a total_amount
      $table->renameColumn('net_amount', 'total_amount');

      // Renombrar total_cost a subtotal
      $table->renameColumn('total_cost', 'subtotal');

      // Agregar de nuevo la columna unit_cost
      $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity_used');
    });
  }
};

