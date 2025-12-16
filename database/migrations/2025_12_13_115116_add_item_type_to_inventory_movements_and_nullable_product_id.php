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
        // Agregar item_type a inventory_movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->enum('item_type', ['PRODUCTO', 'SERVICIO'])->default('PRODUCTO')->after('movement_type');
        });

        // Hacer product_id nullable en inventory_movement_details
        Schema::table('inventory_movement_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir item_type de inventory_movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });

        // Revertir product_id a NOT NULL en inventory_movement_details (si es necesario)
        Schema::table('inventory_movement_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
