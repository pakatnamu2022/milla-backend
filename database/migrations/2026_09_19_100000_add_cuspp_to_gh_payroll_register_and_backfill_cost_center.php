<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * gh_payroll_register.cuspp: snapshot del CUSPP del trabajador (rrhh_persona.cuspp).
 *
 * También rellena cost_center en los registros ya generados: el servicio lo leía de un
 * campo inexistente (sede->nombre) y quedó vacío; el centro de costo real es
 * rrhh_persona.centro_costo_id → rrhh_centro_costo.name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->string('cuspp', 30)->nullable()->after('worker_vat');
        });

        DB::statement('
            UPDATE gh_payroll_register r
            JOIN rrhh_persona p ON p.id = r.worker_id
            LEFT JOIN rrhh_centro_costo c ON c.id = p.centro_costo_id
            SET r.cuspp = NULLIF(p.cuspp, \'\'),
                r.cost_center = COALESCE(c.name, r.cost_center)
        ');
    }

    public function down(): void
    {
        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->dropColumn('cuspp');
        });
    }
};
