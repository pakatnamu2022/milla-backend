<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índice al campo type de ap_masters para optimizar
     * el scope ofType() y las queries de filtrado por type
     */
    public function up(): void
    {
        Schema::table('ap_masters', function (Blueprint $table) {
            // Índice para el campo type - usado frecuentemente en scope ofType()
            $table->index('type', 'idx_ap_masters_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_masters', function (Blueprint $table) {
            $table->dropIndex('idx_ap_masters_type');
        });
    }
};
