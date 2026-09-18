<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pólizas anuales de Seguro Vida Ley por empresa. El cálculo se hace UNA sola vez al emitir/renovar
 * la póliza (réplica de "CALCULO VIDA LEY TP - POR PERSONA POLIZA 2025-2026.xlsx") y el monto
 * mensual de cada trabajador queda congelado en el detalle durante toda la vigencia, aunque su
 * sueldo cambie. gh_payroll_register solo lee ese monto vía life_insurance_policy_worker_id.
 *
 *  total asegurado  = suma(sueldo asegurado) - exclusion
 *  prima neta       = total asegurado x tasa mensual x 12 x (dias / 365)
 *  costo por persona= prima neta x sueldo asegurado / total asegurado
 *  total con IGV    = costo x (1 + IGV)
 *  mensual          = total con IGV / (12 x dias / 365)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_life_insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('insurer')->nullable();
            $table->string('policy_number')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->decimal('monthly_rate', 8, 6);
            $table->decimal('igv_rate', 6, 4);
            $table->decimal('exclusion', 14, 2)->default(0);
            $table->decimal('total_insured_salary', 14, 2)->default(0);
            $table->decimal('net_premium', 14, 4)->default(0);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('set null');

            $table->index(['company_id', 'start_date', 'end_date'], 'gh_life_policies_company_validity_index');
        });

        Schema::create('gh_life_insurance_policy_workers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->integer('worker_id');
            $table->decimal('insured_salary', 12, 2);
            $table->decimal('net_cost', 14, 4);
            $table->decimal('total_with_igv', 14, 4);
            $table->decimal('monthly_amount', 14, 4);
            $table->timestamps();

            $table->foreign('policy_id')->references('id')->on('gh_life_insurance_policies')->onDelete('cascade');
            $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');

            $table->unique(['policy_id', 'worker_id'], 'gh_life_policy_workers_unique');
        });

        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->unsignedBigInteger('life_insurance_policy_worker_id')->nullable()->after('life_insurance');
            $table->foreign('life_insurance_policy_worker_id', 'gh_register_life_policy_worker_fk')
                ->references('id')->on('gh_life_insurance_policy_workers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->dropForeign('gh_register_life_policy_worker_fk');
            $table->dropColumn('life_insurance_policy_worker_id');
        });

        Schema::dropIfExists('gh_life_insurance_policy_workers');
        Schema::dropIfExists('gh_life_insurance_policies');
    }
};
