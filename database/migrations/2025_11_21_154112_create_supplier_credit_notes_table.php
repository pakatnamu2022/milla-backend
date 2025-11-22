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
    Schema::create('supplier_credit_notes', function (Blueprint $table) {
      $table->id();

      // Credit Note Number - Número de Nota de Crédito
      // Número único de la nota de crédito del proveedor
      $table->string('credit_note_number', 50)->unique()->comment('Supplier credit note number');

      // Purchase Order - Orden de Compra
      // Relación con la orden de compra que origina la NC
      $table->foreignId('purchase_order_id')->nullable()->constrained('ap_purchase_order')->nullOnDelete()->comment('Related purchase order');

      // Purchase Reception - Recepción de Compra
      // Relación con la recepción que origina la NC
      $table->foreignId('purchase_reception_id')->nullable()->constrained('purchase_receptions')->nullOnDelete()->comment('Related purchase reception');

      // Supplier - Proveedor
      // Relación con el proveedor (BusinessPartners)
      $table->foreignId('supplier_id')->constrained('business_partners')->comment('Supplier reference');

      // Credit Note Date - Fecha de NC
      // Fecha de emisión de la nota de crédito
      $table->date('credit_note_date')->comment('Credit note issue date');

      // Reason - Motivo
      // Razón de la nota de crédito
      $table->enum('reason', [
        'SHORTAGE',              // Faltante en recepción
        'RETURN',                // Devolución de mercadería
        'DISCOUNT',              // Descuento comercial
        'BILLING_ERROR',         // Error en facturación
        'DAMAGED_GOODS',         // Mercadería dañada
        'PRICE_ADJUSTMENT',      // Ajuste de precio
      ])->comment('Credit note reason');

      // Subtotal - Subtotal
      // Subtotal de la NC (sin IGV)
      $table->decimal('subtotal', 10, 2)->default(0)->comment('Subtotal (excluding tax)');

      // Tax Amount - Monto de IGV
      // Monto del IGV
      $table->decimal('tax_amount', 10, 2)->default(0)->comment('Tax amount (IGV)');

      // Total - Total
      // Total de la NC (con IGV)
      $table->decimal('total', 10, 2)->default(0)->comment('Total amount (including tax)');

      // Status - Estado
      // Estado de la nota de crédito
      $table->enum('status', [
        'DRAFT',                 // Borrador
        'PENDING_APPROVAL',      // Pendiente de aprobación
        'APPROVED',              // Aprobada
        'APPLIED',               // Aplicada (afectó cuentas)
        'REJECTED',              // Rechazada
        'CANCELLED',             // Cancelada
      ])->default('DRAFT')->comment('Credit note status');

      // Notes - Notas
      // Observaciones de la NC
      $table->text('notes')->nullable()->comment('Credit note notes');

      // Approved By - Aprobado Por
      // Usuario que aprueba la NC
      $table->integer('approved_by')->comment('User who approved the credit note');
      $table->foreign('approved_by')
        ->references('id')->on('usr_users');

      // Approved At - Fecha de Aprobación
      // Fecha y hora de aprobación
      $table->timestamp('approved_at')->nullable()->comment('Approval date and time');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('credit_note_number');
      $table->index('purchase_order_id');
      $table->index('purchase_reception_id');
      $table->index('supplier_id');
      $table->index('status');
      $table->index('credit_note_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('supplier_credit_notes');
  }
};
