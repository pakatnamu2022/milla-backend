<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tasas SCTR por empresa con vigencia por fecha. Antes eran un único valor global
 * (general_masters ids 55/56) que no distinguía empresa ni cuándo cambió la tasa.
 *
 * effective_to = null significa "vigente hasta nuevo aviso"; al crear una tasa nueva para una
 * empresa, la anterior se cierra con effective_to = día antes del effective_from de la nueva
 * (ver SctrRate::createAndCloseCurrent()).
 *
 * gh_payroll_register.sctr_rate_id congela qué tasa se usó en cada registro, para que recalcular
 * o consultar una planilla antigua no dependa de la tasa vigente hoy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_sctr_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->decimal('health_rate', 8, 6);
            $table->decimal('pension_rate', 8, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('set null');

            $table->index(['company_id', 'effective_from', 'effective_to'], 'gh_sctr_rates_company_validity_index');
        });

        // Semilla: la tasa global vigente hasta hoy (general_masters ids 55/56) se replica
        // por empresa como "vigente desde siempre", para que las planillas ya generadas
        // y las que se generen antes de cargar tasas nuevas den el mismo resultado.
        $health = DB::table('general_masters')->where('id', 55)->value('value') ?? '0.005';
        $pension = DB::table('general_masters')->where('id', 56)->value('value') ?? '0.005';

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('gh_sctr_rates')->insert([
                'company_id' => $companyId,
                'health_rate' => $health,
                'pension_rate' => $pension,
                'effective_from' => '2000-01-01',
                'effective_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->unsignedBigInteger('sctr_rate_id')->nullable()->after('sctr_pension');
            $table->foreign('sctr_rate_id')->references('id')->on('gh_sctr_rates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->dropForeign(['sctr_rate_id']);
            $table->dropColumn('sctr_rate_id');
        });

        Schema::dropIfExists('gh_sctr_rates');
    }
};
