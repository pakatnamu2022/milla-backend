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
        Schema::table('op_despacho_item', function (Blueprint $table) {
            $table->decimal('tiempo_estimado', 12, 2)->nullable()->change();
            $table->decimal('cantidad', 12, 4)->nullable()->change();
            $table->decimal('precio_unit', 12, 3)->nullable()->change();
            $table->decimal('total', 12, 2)->nullable()->change();
            $table->decimal('km_viaje', 12, 2)->nullable()->change();
            $table->string('tipo_flete', 50)->nullable()->change();
            $table->string('observacion', 359)->nullable()->change();
            $table->integer('unidad_medida_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('op_despacho_item', function (Blueprint $table) {
            $table->decimal('tiempo_estimado', 12, 2)->nullable(false)->change();
            $table->decimal('cantidad', 12, 4)->nullable(false)->change();
            $table->decimal('precio_unit', 12, 3)->nullable(false)->change();
            $table->decimal('total', 12, 2)->nullable(false)->change();
            $table->decimal('km_viaje', 12, 2)->nullable(false)->change();
            $table->string('tipo_flete', 50)->nullable(false)->change();
            $table->string('observacion', 359)->nullable(false)->change();
            $table->integer('unidad_medida_id')->nullable(false)->change();
        });
    }
};
