<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El ENUM de `step` incluía accounting_entry_header/accounting_entry_detail (asiento
 * original) pero NUNCA se agregaron accounting_entry_header_REVERSAL /
 * accounting_entry_detail_REVERSAL cuando se construyó ReverseAccountingEntryJob.
 * Efecto real detectado en producción: al despachar el job, el primer
 * getOrCreateLog() del paso REVERSAL fallaba con
 * "SQLSTATE[01000]: Warning: 1265 Data truncated for column 'step'", la excepción
 * se relanzaba pero el reintento en cola no dejaba rastro visible (sin
 * storage/logs accesible ni entrada en failed_jobs al momento de revisar), dando la
 * falsa impresión de que el job "no hacía nada".
 */
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
    'asset_transaction',
    'asset_transaction_detail',
    'asset_transaction_serial',
  ];

  private array $accountingEntryReversalSteps = [
    'accounting_entry_header_REVERSAL',
    'accounting_entry_detail_REVERSAL',
  ];

  public function up(): void
  {
    $steps = array_merge($this->baseSteps, $this->accountingEntryReversalSteps);
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
