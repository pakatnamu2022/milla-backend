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
    Schema::create('products', function (Blueprint $table) {
      $table->id();

      // Internal Code - Código Interno
      // Código único generado internamente para identificar el producto
      $table->string('code', 50)->unique()->comment('Internal unique product code');

      // Dynamics Code - Código Dynamics
      // Código del producto en Microsoft Dynamics 365
      $table->string('dyn_code', 50)->nullable()->unique()->comment('Microsoft Dynamics 365 product code');

      // Nubefact Code - Código Nubefact
      // Código del producto para facturación electrónica (SUNAT Perú)
      $table->string('nubefac_code', 50)->nullable()->comment('Electronic billing code for SUNAT Peru');

      // Name - Nombre
      // Nombre comercial del producto (repuesto, aceite, etc.)
      $table->string('name')->comment('Commercial product name');

      // Description - Descripción
      // Descripción detallada del producto
      $table->text('description')->nullable()->comment('Detailed product description');

      // Product Category - Categoría de Producto
      // Relación con la categoría (repuestos, lubricantes, accesorios, etc.)
      $table->foreignId('product_category_id')->constrained('product_category')->comment('Product category (spare parts, oils, accessories, etc.)');

      // Brand - Marca
      // Marca del producto (Toyota, Castrol, etc.)
      $table->foreignId('brand_id')->nullable()->constrained('ap_vehicle_brand')->nullOnDelete()->comment('Product brand (Toyota, Castrol, etc.)');

      // Unit of Measurement - Unidad de Medida
      // Unidad de medida del producto (unidad, litro, galón, kilogramo, etc.)
      $table->foreignId('unit_measurement_id')->constrained('unit_measurement')->comment('Unit of measurement (unit, liter, gallon, kilogram, etc.)');

      // Warehouse - Almacén
      // Almacén principal donde se almacena el producto
      $table->foreignId('warehouse_id')->nullable()->constrained('warehouse')->nullOnDelete()->comment('Main warehouse where product is stored');

      // ApClassArticle - Clase de Artículo AP
      // Clase del artículo según catálogo AP (consumible, repuesto, herramienta, etc.)
      $table->foreignId('ap_class_article_id')->constrained('ap_class_article')->comment('AP article class (consumable, spare part, tool, etc.)');
      
      // Product Type - Tipo de Producto
      // Tipo: GOOD (bien físico), SERVICE (servicio), KIT (conjunto de productos)
      $table->enum('product_type', ['GOOD', 'SERVICE', 'KIT'])->default('GOOD')->comment('Product type: GOOD (physical item), SERVICE, KIT (bundle)');

      // Minimum Stock - Stock Mínimo
      // Cantidad mínima que debe haber en inventario
      $table->decimal('minimum_stock', 10, 2)->default(0)->comment('Minimum inventory quantity alert threshold');

      // Maximum Stock - Stock Máximo
      // Cantidad máxima recomendada en inventario
      $table->decimal('maximum_stock', 10, 2)->nullable()->comment('Maximum recommended inventory quantity');

      // Current Stock - Stock Actual
      // Cantidad actual en inventario
      $table->decimal('current_stock', 10, 2)->default(0)->comment('Current inventory quantity');

      // Cost Price - Precio de Costo
      // Precio de costo del producto (sin IGV)
      $table->decimal('cost_price', 10, 2)->default(0)->comment('Product cost price (excluding taxes)');

      // Sale Price - Precio de Venta al Público
      // Precio de venta al público (sin IGV)
      $table->decimal('sale_price', 10, 2)->default(0)->comment('Public sale price (excluding taxes)');

      // Tax Rate - Tasa de Impuesto
      // Porcentaje de IGV (18% en Perú)
      $table->decimal('tax_rate', 5, 2)->default(18.00)->comment('Tax rate percentage (IGV in Peru is 18%)');

      // Taxable - Afecto a Impuesto
      // Si el producto está afecto a IGV
      $table->boolean('is_taxable')->default(true)->comment('Whether the product is subject to tax (IGV)');

      // SUNAT Code - Código SUNAT
      // Código del tipo de bien según catálogo SUNAT (Perú)
      $table->string('sunat_code', 20)->nullable()->comment('SUNAT product type code (Peru tax authority)');

      // Warranty Months - Meses de Garantía
      // Meses de garantía del producto
      $table->integer('warranty_months')->nullable()->comment('Product warranty period in months');

      // Status - Estado
      // Estado del producto: activo, inactivo, descontinuado
      $table->enum('status', ['ACTIVE', 'INACTIVE', 'DISCONTINUED'])->default('ACTIVE')->comment('Product status: active, inactive, discontinued');

      $table->timestamps();
      $table->softDeletes();

      // Indexes for better performance
      $table->index('code');
      $table->index('dyn_code');
      $table->index('product_category_id');
      $table->index('brand_id');
      $table->index('status');
      $table->index('product_type');
      $table->index(['status', 'product_type']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('products');
  }
};
