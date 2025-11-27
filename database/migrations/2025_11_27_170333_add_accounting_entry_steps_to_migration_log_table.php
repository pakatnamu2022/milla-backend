<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // Modificar el enum para agregar los nuevos steps de asientos contables
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
                'inventory_transfer_serial',
                'inventory_transfer_REVERSAL',
                'inventory_transfer_detail_REVERSAL',
                'inventory_transfer_serial_REVERSAL',
                'sale_shipping_guide',
                'sale_shipping_guide_detail',
                'sale_shipping_guide_serial',
                'sale_shipping_guide_REVERSAL',
                'sale_shipping_guide_detail_REVERSAL',
                'sale_shipping_guide_serial_REVERSAL',
                'sales_client',
                'sales_article',
                'sales_document',
                'sales_document_detail',
                'sales_document_serial',
                'accounting_entry_header',
                'accounting_entry_detail'
            ) NOT NULL COMMENT 'Paso del proceso de migración'");
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Revertir a los valores originales del enum
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
                'inventory_transfer_serial',
                'inventory_transfer_REVERSAL',
                'inventory_transfer_detail_REVERSAL',
                'inventory_transfer_serial_REVERSAL',
                'sale_shipping_guide',
                'sale_shipping_guide_detail',
                'sale_shipping_guide_serial',
                'sale_shipping_guide_REVERSAL',
                'sale_shipping_guide_detail_REVERSAL',
                'sale_shipping_guide_serial_REVERSAL',
                'sales_client',
                'sales_article',
                'sales_document',
                'sales_document_detail',
                'sales_document_serial'
            ) NOT NULL COMMENT 'Paso del proceso de migración'");
  }
};
