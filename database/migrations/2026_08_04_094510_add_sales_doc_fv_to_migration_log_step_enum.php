<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    DB::statement("
      ALTER TABLE ap_vehicle_purchase_order_migration_log
      MODIFY COLUMN step ENUM(
        'supplier','supplier_address','article',
        'purchase_order','purchase_order_detail',
        'reception','reception_detail','reception_detail_serial',
        'inventory_transfer','inventory_transfer_detail','inventory_transfer_serial',
        'inventory_transfer_REVERSAL','inventory_transfer_detail_REVERSAL','inventory_transfer_serial_REVERSAL',
        'sale_shipping_guide','sale_shipping_guide_detail','sale_shipping_guide_serial',
        'sale_shipping_guide_REVERSAL','sale_shipping_guide_detail_REVERSAL','sale_shipping_guide_serial_REVERSAL',
        'sales_client','sales_article','sales_document','sales_document_detail','sales_document_serial',
        'accounting_entry_header','accounting_entry_detail',
        'sales_doc_fv'
      ) NOT NULL
    ");
  }

  public function down(): void
  {
    DB::statement("
      ALTER TABLE ap_vehicle_purchase_order_migration_log
      MODIFY COLUMN step ENUM(
        'supplier','supplier_address','article',
        'purchase_order','purchase_order_detail',
        'reception','reception_detail','reception_detail_serial',
        'inventory_transfer','inventory_transfer_detail','inventory_transfer_serial',
        'inventory_transfer_REVERSAL','inventory_transfer_detail_REVERSAL','inventory_transfer_serial_REVERSAL',
        'sale_shipping_guide','sale_shipping_guide_detail','sale_shipping_guide_serial',
        'sale_shipping_guide_REVERSAL','sale_shipping_guide_detail_REVERSAL','sale_shipping_guide_serial_REVERSAL',
        'sales_client','sales_article','sales_document','sales_document_detail','sales_document_serial',
        'accounting_entry_header','accounting_entry_detail'
      ) NOT NULL
    ");
  }
};
