<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds critical indexes for inventory movement history queries performance.
     * These indexes optimize:
     * - Product movement history by date range (getProductMovementHistory)
     * - Warehouse stock calculations
     * - Transfer operations between warehouses
     */
    public function up(): void
    {
        // CRITICAL: Index for inventory_movement_details queries by product
        // This dramatically improves JOIN performance when filtering by product_id
        Schema::table('inventory_movement_details', function (Blueprint $table) {
            $table->index(['product_id', 'inventory_movement_id'], 'idx_movement_details_product');
        });

        // IMPORTANT: Index for warehouse_destination_id
        // Needed for TRANSFER_IN operations and warehouse destination filtering
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index('warehouse_destination_id', 'idx_movements_warehouse_destination');
        });

        // OPTIONAL but RECOMMENDED: Composite index for complex queries
        // Optimizes queries that filter by date + type + warehouse simultaneously
        // This is the main query pattern in getProductMovementHistory
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['movement_date', 'movement_type', 'warehouse_id'], 'idx_movements_date_type_warehouse');
        });

        // BONUS: Composite index for destination warehouse queries
        // Useful for transfer operations and multi-warehouse reports
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['movement_date', 'warehouse_destination_id'], 'idx_movements_date_warehouse_dest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movement_details', function (Blueprint $table) {
            $table->dropIndex('idx_movement_details_product');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('idx_movements_warehouse_destination');
            $table->dropIndex('idx_movements_date_type_warehouse');
            $table->dropIndex('idx_movements_date_warehouse_dest');
        });
    }
};
