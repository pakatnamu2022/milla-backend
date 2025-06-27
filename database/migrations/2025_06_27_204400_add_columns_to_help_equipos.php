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
        Schema::table('help_equipos', function (Blueprint $table) {
            $table->string('tipo_adquisicion')->nullable()->after('status_deleted');
            $table->string('factura')->nullable()->after('tipo_adquisicion');
            $table->string('contrato')->nullable()->after('factura');
            $table->string('proveedor')->nullable()->after('contrato');
            $table->date('fecha_adquisicion')->nullable()->after('proveedor');
            $table->date('fecha_garantia')->nullable()->after('fecha_adquisicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('help_equipos', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_adquisicion',
                'factura',
                'contrato',
                'proveedor',
                'fecha_adquisicion',
                'fecha_garantia'
            ]);
        });
    }
};
