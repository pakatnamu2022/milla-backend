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
     * - Filtros por area_id, sede_id, status_id, quotation_date
     * - Eager loading de relaciones más usadas
     */
    public function up(): void
    {
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            // Índice compuesto principal para la query más común del frontend
            // Orden: igualdad -> igualdad -> IN -> rango de fechas
            // Cubre: WHERE area_id = X AND sede_id = Y AND status_id IN (...) AND quotation_date BETWEEN ...
            // También cubre: WHERE area_id = X (sin redundancia)
            // También cubre: WHERE area_id = X AND sede_id = Y (sin redundancia)
            $table->index(
                ['area_id', 'sede_id', 'status_id', 'quotation_date'],
                'idx_aoq_area_sede_status_date'
            );

            // Índice de fecha para queries que solo filtran por quotation_date (sin otros filtros)
            $table->index('quotation_date', 'idx_aoq_quotation_date');

            // Índice de estado para queries que solo filtran por status_id (sin otros filtros)
            $table->index('status_id', 'idx_aoq_status_id');

            // Índices para foreign keys MÁS USADAS en eager loading
            $table->index('created_by', 'idx_aoq_created_by');
            $table->index('discarded_by', 'idx_aoq_discarded_by');

            // Índice para parent_quotation_id (cotizaciones segmentadas - filtro común)
            $table->index('parent_quotation_id', 'idx_aoq_parent_quotation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            // Eliminar índices en orden inverso
            $table->dropIndex('idx_aoq_parent_quotation_id');
            $table->dropIndex('idx_aoq_discarded_by');
            $table->dropIndex('idx_aoq_created_by');
            $table->dropIndex('idx_aoq_status_id');
            $table->dropIndex('idx_aoq_quotation_date');
            $table->dropIndex('idx_aoq_area_sede_status_date');
        });
    }
};
