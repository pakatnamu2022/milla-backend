<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // Agregar los nuevos valores al enum de 'step'
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      $table->enum('step', [
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
        'sales_client',
        'sales_article',
        'sales_document',
        'sales_document_detail',
        'sales_document_serial',
        'sales_doc_fv',
        'sale_shipping_guide',
        'sale_shipping_guide_detail',
        'sale_shipping_guide_serial',
        'sale_shipping_guide_REVERSAL',
        'sale_shipping_guide_detail_REVERSAL',
        'sale_shipping_guide_serial_REVERSAL',
        'asset_transaction',
        'asset_transaction_detail',
        'asset_transaction_serial',
        'accounting_entry_header',
        'accounting_entry_detail',
        'accounting_entry_header_REVERSAL',
        'accounting_entry_detail_REVERSAL',
        'internal_note_transaction',
        'internal_note_transaction_detail',
        'internal_note_transaction_REVERSAL',
        'internal_note_transaction_detail_REVERSAL',
        // Nuevos valores para traverse
        'traverse_adjustment',
        'traverse_adjustment_detail',
        'traverse_adjustment_REVERSAL',
        'traverse_adjustment_detail_REVERSAL',
        'traverse_accounting_entry_header',
        'traverse_accounting_entry_detail',
        'traverse_accounting_entry_header_REVERSAL',
        'traverse_accounting_entry_detail_REVERSAL'
      ])->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Revertir al enum original (sin los pasos de traverse)
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      $table->enum('step', [
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
        'sales_client',
        'sales_article',
        'sales_document',
        'sales_document_detail',
        'sales_document_serial',
        'sales_doc_fv',
        'sale_shipping_guide',
        'sale_shipping_guide_detail',
        'sale_shipping_guide_serial',
        'sale_shipping_guide_REVERSAL',
        'sale_shipping_guide_detail_REVERSAL',
        'sale_shipping_guide_serial_REVERSAL',
        'asset_transaction',
        'asset_transaction_detail',
        'asset_transaction_serial',
        'accounting_entry_header',
        'accounting_entry_detail',
        'accounting_entry_header_REVERSAL',
        'accounting_entry_detail_REVERSAL',
        'internal_note_transaction',
        'internal_note_transaction_detail',
        'internal_note_transaction_REVERSAL',
        'internal_note_transaction_detail_REVERSAL'
      ])->change();
    });
  }
};
