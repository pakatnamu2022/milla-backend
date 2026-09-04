<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  private array $baseSteps = [
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
    'accounting_entry_detail',
    'sales_doc_fv',
    'internal_note_transaction',
    'internal_note_transaction_detail',
    'internal_note_transaction_REVERSAL',
    'internal_note_transaction_detail_REVERSAL',
  ];

  private array $assetSteps = [
    'asset_transaction',
    'asset_transaction_detail',
    'asset_transaction_serial',
  ];

  public function up(): void
  {
    $steps = array_merge($this->baseSteps, $this->assetSteps);
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) use ($steps) {
      $table->enum('step', $steps)->comment('Paso del proceso de migración')->change();
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      $table->enum('step', $this->baseSteps)->comment('Paso del proceso de migración')->change();
    });
  }
};
