<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subsidios por incapacidad temporal / maternidad (los paga EsSalud desde el día 21 de
 * descanso médico; los primeros 20 días los paga el empleador como DM).
 *
 * Un registro es el certificado completo (start_date..end_date, days, amount total). El
 * PayrollRegisterService reparte días y monto por periodo según cuántos días del rango caen
 * dentro de cada mes, así un subsidio que cruza de mes no hay que partirlo a mano.
 *
 * gh_payroll_register.vacation_average / vacation_daily_value congelan el promedio de
 * variables y el valor diario usados para la remuneración vacacional de ese registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_payroll_subsidies', function (Blueprint $table) {
            $table->id();
            $table->integer('worker_id');
            $table->string('type', 30)->default('INCAPACIDAD_TEMPORAL');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->decimal('amount', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->string('notes')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('set null');

            $table->index(['worker_id', 'start_date', 'end_date'], 'gh_payroll_subsidies_worker_dates_index');
        });

        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->decimal('vacation_average', 12, 2)->default(0)->after('vacation_pay');
            $table->decimal('vacation_daily_value', 12, 4)->default(0)->after('vacation_average');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_register', function (Blueprint $table) {
            $table->dropColumn(['vacation_average', 'vacation_daily_value']);
        });

        Schema::dropIfExists('gh_payroll_subsidies');
    }
};
