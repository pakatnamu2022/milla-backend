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
        Schema::table('op_gastos_viaje', function(Blueprint $table) {
            $table->integer('liquidacion_id')->nullable()->change();
            $table->string('numero_doc', 250)->nullable()->change();
            $table->date('fecha_emision')->nullable()->change();
            $table->decimal('km_tanqueo', 10, 2)->nullable()->change();
            $table->decimal('punto_tanqueo_id', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('op_gastos_viaje', function(Blueprint $table) {
            $table->integer('liquidacion_id')->nullable(false)->change();
            $table->date('fecha_emision')->nullable(false)->change();
            $table->decimal('km_tanqueo', 10, 2)->nullable(false)->change();
            $table->decimal('punto_tanqueo_id', 10, 2)->nullable(false)->change();
        });
    }
};
