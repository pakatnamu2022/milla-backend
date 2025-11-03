<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // Modificar el ENUM del campo 'step' para agregar los pasos de Shipping Guides
    DB::statement("ALTER TABLE ap_vehicle_purchase_order_migration_log
            MODIFY COLUMN step ENUM(
                'supplier',
                'supplier_address',
                'article',
                'purchase_order',
                'purchase_order_detail',
                'reception',
                'reception_detail',
                'reception_detail_serial',
                'inventory_transfer',
                'inventory_transfer_detail',
                'inventory_transfer_serial'
            ) NOT NULL COMMENT 'Paso del proceso de migración'");
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Revertir el ENUM al estado original (solo pasos de Purchase Orders)
    DB::statement("ALTER TABLE ap_vehicle_purchase_order_migration_log
            MODIFY COLUMN step ENUM(
                'supplier',
                'supplier_address',
                'article',
                'purchase_order',
                'purchase_order_detail',
                'reception',
                'reception_detail',
                'reception_detail_serial'
            ) NOT NULL COMMENT 'Paso del proceso de migración'");
  }
};
