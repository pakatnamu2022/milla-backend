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
        // Agregar item_type a transfer_receptions
        Schema::table('transfer_receptions', function (Blueprint $table) {
            $table->enum('item_type', ['PRODUCTO', 'SERVICIO'])->default('PRODUCTO')->after('id');
        });

        // Hacer product_id nullable en transfer_reception_details
        Schema::table('transfer_reception_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir item_type de transfer_receptions
        Schema::table('transfer_receptions', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });

        // Revertir product_id a NOT NULL en transfer_reception_details (si es necesario)
        Schema::table('transfer_reception_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
