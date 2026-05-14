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
      // Referencia al movimiento de inventario original que se está cancelando
      $table->foreignId('cancelled_inventory_movement_id')
        ->nullable()
        ->after('reference_id')
        ->references('id', 'fk_inv_mov_cancelled_ref')
        ->on('inventory_movements')
        ->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->dropForeign('fk_inv_mov_cancelled_ref');
      $table->dropColumn('cancelled_inventory_movement_id');
    });
  }
};
