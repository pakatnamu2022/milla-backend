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
    Schema::create('inventory_movements', function (Blueprint $table) {
      $table->id();

      // Movement Number - Número de Movimiento
      // Número único correlativo del movimiento (MOV-2025-0001)
      $table->string('movement_number', 50)->unique()->comment('Unique movement number (MOV-2025-0001)');

      // Movement Type - Tipo de Movimiento
      // Tipo de operación que genera el movimiento
      $table->enum('movement_type', [
        'PURCHASE_RECEPTION',    // Recepción de compra
        'SALE',                  // Venta
        'ADJUSTMENT_IN',         // Ajuste positivo (encontraste stock)
        'ADJUSTMENT_OUT',        // Ajuste negativo (merma, robo)
        'TRANSFER_OUT',          // Transferencia salida (origen)
        'TRANSFER_IN',           // Transferencia entrada (destino)
        'RETURN_IN',             // Devolución de cliente (ingreso)
        'RETURN_OUT',            // Devolución a proveedor (salida)
        'LOSS',                  // Pérdida
        'DAMAGE',                // Producto dañado
      ])->comment('Type of inventory movement');

      // Movement Date - Fecha de Movimiento
      // Fecha en que se realiza el movimiento
      $table->date('movement_date')->comment('Date of the movement');

      // Warehouse - Almacén Origen/Destino
      // Almacén donde se realiza el movimiento
      $table->foreignId('warehouse_id')->constrained('warehouse')->comment('Origin/destination warehouse');

      // Warehouse Destination - Almacén Destino (solo para transferencias)
      // Solo se usa cuando movement_type = TRANSFER_OUT
      $table->foreignId('warehouse_destination_id')->nullable()->constrained('warehouse')->comment('Destination warehouse (only for transfers)');

      // Reference Type - Tipo de Referencia (Polymorphic)
      // Tipo de documento que origina el movimiento
      $table->string('reference_type')->nullable()->comment('Reference document type (PurchaseReception, Sale, etc.)');

      // Reference ID - ID de Referencia (Polymorphic)
      // ID del documento que origina el movimiento
      $table->unsignedBigInteger('reference_id')->nullable()->comment('Reference document ID');

      // User - Usuario que realiza el movimiento
      $table->integer('user_id')->comment('User who performed the movement');
      $table->foreign('user_id')
        ->references('id')->on('usr_users');

      // Status - Estado del Movimiento
      // Estado del movimiento
      $table->enum('status', [
        'DRAFT',                 // Borrador
        'APPROVED',              // Aprobado (afecta inventario)
        'CANCELLED',             // Cancelado
      ])->default('DRAFT')->comment('Movement status');

      // Notes - Notas
      // Observaciones del movimiento
      $table->text('notes')->nullable()->comment('Movement notes');

      // Total Items - Total de Items
      // Cantidad de líneas de detalle
      $table->integer('total_items')->default(0)->comment('Total number of detail lines');

      // Total Quantity - Cantidad Total
      // Suma de todas las cantidades del detalle
      $table->decimal('total_quantity', 10, 2)->default(0)->comment('Total quantity of all details');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('movement_number');
      $table->index('movement_type');
      $table->index('movement_date');
      $table->index('warehouse_id');
      $table->index('status');
      $table->index(['reference_type', 'reference_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('inventory_movements');
  }
};
