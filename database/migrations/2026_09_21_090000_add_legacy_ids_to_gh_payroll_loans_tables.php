<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad con el legacy web_millagp_2 para la sincronización de préstamos.
 *
 * gh_payroll_loans.legacy_id                 → rrhh_prestamos.id
 * gh_payroll_loan_extra_discounts.legacy_detail_id → rrhh_detalle_prestamo.id
 *
 * Permiten repetir la sincronización sin duplicar filas. Los préstamos creados
 * directamente en este sistema quedan con legacy_id NULL y la sincronización
 * nunca los toca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gh_payroll_loans', function (Blueprint $table) {
            $table->unsignedInteger('legacy_id')->nullable()->unique()->after('id');
        });

        Schema::table('gh_payroll_loan_extra_discounts', function (Blueprint $table) {
            $table->unsignedInteger('legacy_detail_id')->nullable()->unique()->after('loan_id');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_loan_extra_discounts', function (Blueprint $table) {
            $table->dropUnique(['legacy_detail_id']);
            $table->dropColumn('legacy_detail_id');
        });

        Schema::table('gh_payroll_loans', function (Blueprint $table) {
            $table->dropUnique(['legacy_id']);
            $table->dropColumn('legacy_id');
        });
    }
};
