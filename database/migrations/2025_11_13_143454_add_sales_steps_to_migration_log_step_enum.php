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
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Agregar los pasos de reversión (cancelación) al ENUM
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
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      // Revertir al estado anterior (sin pasos de reversión)
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
                'inventory_transfer_serial_REVERSAL'
            ) NOT NULL COMMENT 'Paso del proceso de migración'");
    });
  }
};
