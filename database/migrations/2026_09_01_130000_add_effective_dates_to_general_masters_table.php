<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vigencia por fecha para general_masters. Varios parámetros de planilla (RMV/SALARIO_MINIMO,
 * UIT, FAMILY_ALLOWANCE) cambian de valor periódicamente por ley, y hasta ahora se leían como un
 * único valor "actual" (GeneralMaster::find($id)->value), sin distinguir qué valor estuvo vigente
 * en cada periodo. Eso hace que recalcular un periodo antiguo después de que el valor cambie use
 * el valor nuevo por error.
 *
 * Con estas columnas, un código puede tener varias filas (una por rango de vigencia): la fila
 * "actual" para un parámetro que nunca cambió mantiene effective_from/effective_to en null (=
 * vigente siempre, comportamiento idéntico al anterior). Para versionar un parámetro, se cierra
 * la fila vigente (effective_to = día antes del cambio) y se crea una fila nueva con
 * effective_from = fecha del cambio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_masters', function (Blueprint $table) {
            $table->date('effective_from')->nullable()->after('value');
            $table->date('effective_to')->nullable()->after('effective_from');
        });
    }

    public function down(): void
    {
        Schema::table('general_masters', function (Blueprint $table) {
            $table->dropColumn(['effective_from', 'effective_to']);
        });
    }
};
