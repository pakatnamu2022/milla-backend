<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rrhh_persona', function (Blueprint $table) {
            $table->string('medio_contacto', 100)->nullable()->after('motivo_status');
            $table->string('disponibilidad', 150)->nullable()->after('medio_contacto');
            $table->string('condiciones_laborales', 255)->nullable()->after('disponibilidad');
            $table->text('experiencia_laboral')->nullable()->after('condiciones_laborales');
            $table->decimal('anos_experiencia', 4, 1)->nullable()->after('experiencia_laboral');
            $table->text('enfermedad_operacion_lesion')->nullable()->after('anos_experiencia');
            $table->string('verificativa_status', 50)->nullable()->after('enfermedad_operacion_lesion');
            $table->text('observacion_verificativa')->nullable()->after('verificativa_status');
            $table->text('comentarios_reclutamiento')->nullable()->after('observacion_verificativa');
        });
    }

    public function down(): void
    {
        Schema::table('rrhh_persona', function (Blueprint $table) {
            $table->dropColumn([
                'medio_contacto',
                'disponibilidad',
                'condiciones_laborales',
                'experiencia_laboral',
                'anos_experiencia',
                'enfermedad_operacion_lesion',
                'verificativa_status',
                'observacion_verificativa',
                'comentarios_reclutamiento',
            ]);
        });
    }
};
