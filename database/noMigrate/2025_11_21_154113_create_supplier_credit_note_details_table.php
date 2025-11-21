<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('supplier_credit_note_details', function (Blueprint $table) {
      $table->id();

      // Credit Note - Nota de Crédito
      // Relación con la cabecera de la nota de crédito
      $table->foreignId('supplier_credit_note_id')->constrained('supplier_credit_notes')->onDelete('cascade')->comment('Credit note reference');

      // Product - Producto
      // Producto afectado por la NC
      $table->foreignId('product_id')->constrained('products')->comment('Product reference');

      // Quantity - Cantidad
      // Cantidad de producto en la NC
      $table->decimal('quantity', 10, 2)->comment('Product quantity');

      // Unit Price - Precio Unitario
      // Precio unitario del producto (sin IGV)
      $table->decimal('unit_price', 10, 2)->default(0)->comment('Unit price (excluding tax)');

      // Discount Percentage - Porcentaje de Descuento
      // Descuento aplicado al producto
      $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Discount percentage');

      // Tax Rate - Tasa de Impuesto
      // Porcentaje de IGV (18% en Perú)
      $table->decimal('tax_rate', 5, 2)->default(18.00)->comment('Tax rate (IGV)');

      // Subtotal - Subtotal
      // Subtotal de la línea (sin IGV)
      $table->decimal('subtotal', 10, 2)->default(0)->comment('Line subtotal (excluding tax)');

      // Notes - Notas
      // Observaciones específicas del detalle
      $table->text('notes')->nullable()->comment('Detail notes');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('supplier_credit_note_id');
      $table->index('product_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('supplier_credit_note_details');
  }
};
