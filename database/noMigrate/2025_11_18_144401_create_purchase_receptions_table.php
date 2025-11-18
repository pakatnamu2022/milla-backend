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
    Schema::create('purchase_receptions', function (Blueprint $table) {
      $table->id();

      // Reception Number - Número de Recepción
      // Número único de la recepción (REC-2025-0001)
      $table->string('reception_number', 50)->unique()->comment('Unique reception number (REC-2025-0001)');

      // Purchase Order - Orden de Compra
      // Relación con la orden de compra que se está recibiendo
      $table->foreignId('purchase_order_id')
        ->constrained('ap_purchase_order')
        ->comment('Related purchase order being received');

      // Reception Date - Fecha de Recepción
      // Fecha en que se recibe físicamente la mercadería
      $table->date('reception_date')->comment('Physical reception date');

      // Warehouse - Almacén
      // Almacén donde se recibe la mercadería (puede ser diferente al de la OC)
      $table->foreignId('warehouse_id')
        ->constrained('warehouse')
        ->comment('Warehouse where products are received');

      // Supplier Invoice - Factura del Proveedor
      // Número de factura que envía el proveedor
      $table->string('supplier_invoice_number', 100)->nullable()->comment('Supplier invoice number');

      // Supplier Invoice Date - Fecha de Factura del Proveedor
      $table->date('supplier_invoice_date')->nullable()->comment('Supplier invoice date');

      // Shipping Guide - Guía de Remisión
      // Número de guía de remisión del proveedor
      $table->string('shipping_guide_number', 100)->nullable()->comment('Shipping guide number from supplier');

      // Status - Estado
      // PENDING_REVIEW: Pendiente de revisión
      // APPROVED: Aprobado y registrado en inventario
      // REJECTED: Rechazado completamente
      // PARTIAL: Parcialmente aceptado (algunos productos rechazados)
      $table->enum('status', ['PENDING_REVIEW', 'APPROVED', 'REJECTED', 'PARTIAL'])
        ->default('PENDING_REVIEW')
        ->comment('Reception status: PENDING_REVIEW, APPROVED, REJECTED, PARTIAL');

      // Reception Type - Tipo de Recepción
      // COMPLETE: Recepción completa de la OC
      // PARTIAL: Recepción parcial (faltan productos)
      $table->enum('reception_type', ['COMPLETE', 'PARTIAL'])
        ->default('PARTIAL')
        ->comment('COMPLETE if all items received, PARTIAL if more receptions pending');

      // Notes - Notas
      // Observaciones generales de la recepción
      $table->text('notes')->nullable()->comment('General reception notes and observations');

      // Received By - Recibido Por
      // Usuario que recibe la mercadería
      $table->integer('received_by')->nullable();
      $table->foreign('received_by')
        ->references('id')->on('usr_users');

      // Reviewed By - Revisado Por
      // Usuario que aprueba/rechaza la recepción
      $table->integer('reviewed_by')->nullable();
      $table->foreign('reviewed_by')
        ->references('id')->on('usr_users');

      // Reviewed At - Fecha de Revisión
      $table->timestamp('reviewed_at')->nullable()->comment('Timestamp when reception was reviewed');

      // Total Received Items - Total de Items Recibidos
      // Cantidad total de items diferentes recibidos (no cantidad de productos)
      $table->integer('total_items')->default(0)->comment('Total number of different items received');

      // Total Quantity - Cantidad Total
      // Suma total de cantidades recibidas
      $table->decimal('total_quantity', 10, 2)->default(0)->comment('Total quantity of all products received');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('reception_number');
      $table->index('purchase_order_id');
      $table->index('warehouse_id');
      $table->index('status');
      $table->index('reception_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('purchase_receptions');
  }
};
