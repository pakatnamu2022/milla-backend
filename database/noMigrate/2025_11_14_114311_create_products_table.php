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

            // Part Number - Número de Parte
            // Número de parte original del fabricante (OEM)
            $table->string('part_number', 100)->nullable()->comment('Original Equipment Manufacturer (OEM) part number');

            // Alternative Part Numbers - Números de Parte Alternativos
            // Números de parte alternativos o compatibles (JSON array)
            $table->json('alternative_part_numbers')->nullable()->comment('Alternative or compatible part numbers (JSON array)');

            // Barcode - Código de Barras
            // Código de barras del producto (EAN, UPC, etc.)
            $table->string('barcode', 100)->nullable()->unique()->comment('Product barcode (EAN, UPC, etc.)');

            // SKU - SKU
            // Stock Keeping Unit - Unidad de mantenimiento de stock
            $table->string('sku', 100)->nullable()->unique()->comment('Stock Keeping Unit');

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

            // Sale Price - Precio de Venta
            // Precio de venta al público (sin IGV)
            $table->decimal('sale_price', 10, 2)->default(0)->comment('Public sale price (excluding taxes)');

            // Wholesale Price - Precio por Mayor
            // Precio para ventas al por mayor
            $table->decimal('wholesale_price', 10, 2)->nullable()->comment('Wholesale price for bulk purchases');

            // Tax Rate - Tasa de Impuesto
            // Porcentaje de IGV (18% en Perú)
            $table->decimal('tax_rate', 5, 2)->default(18.00)->comment('Tax rate percentage (IGV in Peru is 18%)');

            // Taxable - Afecto a Impuesto
            // Si el producto está afecto a IGV
            $table->boolean('is_taxable')->default(true)->comment('Whether the product is subject to tax (IGV)');

            // SUNAT Code - Código SUNAT
            // Código del tipo de bien según catálogo SUNAT (Perú)
            $table->string('sunat_code', 20)->nullable()->comment('SUNAT product type code (Peru tax authority)');

            // Location - Ubicación
            // Ubicación física del producto en almacén (estante, pasillo, etc.)
            $table->string('warehouse_location', 100)->nullable()->comment('Physical warehouse location (shelf, aisle, etc.)');

            // Warranty Months - Meses de Garantía
            // Meses de garantía del producto
            $table->integer('warranty_months')->nullable()->comment('Product warranty period in months');

            // Reorder Point - Punto de Reorden
            // Cantidad en la cual se debe generar una orden de compra
            $table->decimal('reorder_point', 10, 2)->nullable()->comment('Inventory level that triggers purchase order');

            // Reorder Quantity - Cantidad de Reorden
            // Cantidad a ordenar cuando se alcanza el punto de reorden
            $table->decimal('reorder_quantity', 10, 2)->nullable()->comment('Quantity to order when reorder point is reached');

            // Lead Time Days - Días de Tiempo de Entrega
            // Días que tarda en llegar el producto desde que se ordena
            $table->integer('lead_time_days')->nullable()->comment('Days from order to delivery');

            // Weight - Peso
            // Peso del producto en kilogramos
            $table->decimal('weight', 10, 3)->nullable()->comment('Product weight in kilograms');

            // Dimensions - Dimensiones
            // Dimensiones del producto (largo x ancho x alto en cm) JSON
            $table->json('dimensions')->nullable()->comment('Product dimensions (length x width x height in cm) JSON format');

            // Image - Imagen
            // URL de la imagen principal del producto
            $table->string('image')->nullable()->comment('Main product image URL');

            // Images - Imágenes
            // URLs de imágenes adicionales del producto (JSON array)
            $table->json('images')->nullable()->comment('Additional product images URLs (JSON array)');

            // Technical Specifications - Especificaciones Técnicas
            // Especificaciones técnicas del producto (JSON)
            $table->json('technical_specifications')->nullable()->comment('Technical product specifications (JSON format)');

            // Compatible Vehicles - Vehículos Compatibles
            // IDs de modelos de vehículos compatibles (JSON array)
            $table->json('compatible_vehicle_models')->nullable()->comment('Compatible vehicle model IDs (JSON array)');

            // Is Fragile - Es Frágil
            // Si el producto es frágil y requiere manejo especial
            $table->boolean('is_fragile')->default(false)->comment('Whether product requires special handling');

            // Is Perishable - Es Perecedero
            // Si el producto tiene fecha de vencimiento
            $table->boolean('is_perishable')->default(false)->comment('Whether product has expiration date');

            // Expiration Date - Fecha de Vencimiento
            // Fecha de vencimiento del producto (para productos perecederos)
            $table->date('expiration_date')->nullable()->comment('Product expiration date (for perishable items)');

            // Manufacturer - Fabricante
            // Nombre del fabricante del producto
            $table->string('manufacturer', 200)->nullable()->comment('Product manufacturer name');

            // Country of Origin - País de Origen
            // País donde se fabricó el producto
            $table->string('country_of_origin', 100)->nullable()->comment('Manufacturing country');

            // Notes - Notas
            // Notas adicionales sobre el producto
            $table->text('notes')->nullable()->comment('Additional product notes');

            // Status - Estado
            // Estado del producto: activo, inactivo, descontinuado
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'DISCONTINUED'])->default('ACTIVE')->comment('Product status: active, inactive, discontinued');

            // Is Featured - Es Destacado
            // Si el producto es destacado en ventas
            $table->boolean('is_featured')->default(false)->comment('Whether product is featured in sales');

            // Is Best Seller - Es Más Vendido
            // Si el producto es de los más vendidos
            $table->boolean('is_best_seller')->default(false)->comment('Whether product is a best seller');

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
