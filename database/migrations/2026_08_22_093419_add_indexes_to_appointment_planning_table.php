<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índices optimizados para las queries más comunes del frontend:
     * - Filtros por sede_id, date_appointment
     * - Eager loading de relaciones más usadas
     *
     * Optimiza queries de listado con filtros:
     * - sede_id = 13
     * - date_appointment BETWEEN '2026-08-01' AND '2026-08-22'
     */
    public function up(): void
    {
        Schema::table('appointment_planning', function (Blueprint $table) {
            // Índice compuesto principal para la query más común del frontend
            // Orden: igualdad -> rango de fechas
            // Cubre: WHERE sede_id = X AND date_appointment BETWEEN ...
            // También sirve para: WHERE sede_id = X (sin redundancia)
            $table->index(
                ['sede_id', 'date_appointment'],
                'idx_ap_sede_date_appointment'
            );

            // Índice de fecha para queries que solo filtran por date_appointment (sin sede)
            $table->index('date_appointment', 'idx_ap_date_appointment');

            // Índices para foreign keys MÁS USADAS en eager loading
            $table->index('advisor_id', 'idx_ap_advisor_id');
            $table->index('ap_vehicle_id', 'idx_ap_vehicle_id');

            // Índice para delivery_date (si se filtra frecuentemente)
            $table->index('delivery_date', 'idx_ap_delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_planning', function (Blueprint $table) {
            // Eliminar índices en orden inverso
            $table->dropIndex('idx_ap_delivery_date');
            $table->dropIndex('idx_ap_vehicle_id');
            $table->dropIndex('idx_ap_advisor_id');
            $table->dropIndex('idx_ap_date_appointment');
            $table->dropIndex('idx_ap_sede_date_appointment');
        });
    }
};
