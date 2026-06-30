<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * Tabla para materializar el historial de costo promedio ponderado.
   * Esta tabla actúa como un snapshot (fotografía) del estado del stock y costo
   * después de cada movimiento de inventario que afecte el costo promedio.
   *
   * PROPÓSITO:
   * - Evitar recálculos complejos "on the fly" en getPriceCalculationDetails
   * - Permitir consultas rápidas del historial de costos
   * - Facilitar auditoría y trazabilidad
   * - Soportar recálculos retroactivos (ej: NC de hace días)
   */
  public function up(): void
  {
    Schema::create('weighted_average_cost_history', function (Blueprint $table) {
      $table->id();

      // Llave compuesta: producto + almacén
      // Esta combinación define el contexto del historial
      $table->foreignId('product_id')
        ->comment('Product ID')
        ->constrained('products')
        ->onDelete('cascade');

      $table->foreignId('warehouse_id')
        ->comment('Warehouse ID')
        ->constrained('warehouse')
        ->onDelete('cascade');

      // Referencia al movimiento que generó este snapshot
      // Puede ser NULL para snapshots de estado inicial o consolidados
      $table->foreignId('movement_id')
        ->nullable()
        ->comment('Inventory movement that generated this snapshot')
        ->constrained('inventory_movements')
        ->onDelete('set null');

      // Fecha del movimiento (para ordenamiento cronológico)
      // IMPORTANTE: Esta es la fecha de negocio, no la fecha de registro
      $table->date('movement_date')
        ->comment('Date of the movement (business date, not creation date)');

      // Tipo de movimiento (desnormalizado para queries rápidas)
      $table->string('movement_type', 50)
        ->nullable()
        ->comment('Type of movement (PURCHASE_RECEPTION, RETURN_OUT, etc.)');

      // Número del movimiento (desnormalizado para facilitar auditoría)
      $table->string('movement_number', 50)
        ->nullable()
        ->comment('Movement number (MOV-2026-0001)');

      // Cantidades del movimiento
      // Solo UNA de estas será > 0 (entrada o salida)
      $table->decimal('quantity_in', 12, 4)
        ->default(0)
        ->comment('Quantity added to stock (for INBOUND movements)');

      $table->decimal('quantity_out', 12, 4)
        ->default(0)
        ->comment('Quantity removed from stock (for OUTBOUND movements)');

      // Costo unitario del movimiento en PEN (moneda base)
      // Solo relevante para movimientos INBOUND que afectan el costo promedio
      $table->decimal('unit_cost_pen', 12, 2)
        ->default(0)
        ->comment('Unit cost in PEN (base currency)');

      // SNAPSHOTS: Estado DESPUÉS de aplicar este movimiento
      // Estos son los valores calculados que evitan recálculos posteriores
      $table->decimal('stock_after_movement', 12, 4)
        ->comment('Stock quantity AFTER applying this movement');

      $table->decimal('average_cost_after_movement', 12, 2)
        ->comment('Weighted average cost AFTER applying this movement');

      // Metadatos para auditoría y control
      $table->timestamp('recalculated_at')
        ->nullable()
        ->comment('Last time this record was recalculated (for retroactive adjustments)');

      $table->timestamps();

      // INDEXES
      // Índice principal: buscar historial de un producto en un almacén por fecha
      $table->index(['product_id', 'warehouse_id', 'movement_date'], 'idx_product_warehouse_date');

      // Índice para búsquedas por tipo de movimiento
      $table->index(['product_id', 'warehouse_id', 'movement_type'], 'idx_product_warehouse_type');

      // Índice para búsquedas por movimiento específico
      $table->index('movement_id', 'idx_movement_id');

      // UNIQUE CONSTRAINT
      // Un movimiento solo puede generar un snapshot para un producto-almacén
      // NOTA: movement_id puede ser NULL (para snapshots iniciales), por eso no está en UNIQUE
      $table->unique(['product_id', 'warehouse_id', 'movement_id'], 'unique_product_warehouse_movement');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('weighted_average_cost_history');
  }
};
