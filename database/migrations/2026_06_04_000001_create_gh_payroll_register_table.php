<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_payroll_register', function (Blueprint $table) {
            $table->id();

            // ─────────────────────────────────────────────────────────
            // RELACIONES PRINCIPALES
            // ─────────────────────────────────────────────────────────
            $table->foreignId('period_id')
                  ->constrained('gh_payroll_periods')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete()
                  ->comment('FK al período de planilla (gh_payroll_periods)');

            $table->integer('worker_id')
                  ->comment('FK al trabajador (rrhh_persona)');
            $table->foreign('worker_id')
                  ->references('id')
                  ->on('rrhh_persona')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // ─────────────────────────────────────────────────────────
            // SNAPSHOT DEL TRABAJADOR AL MOMENTO DE LA PLANILLA
            // (copia inmutable para preservar el historial)
            // ─────────────────────────────────────────────────────────
            $table->string('worker_name')->comment('Nombre completo del trabajador en el momento de la planilla (snapshot de nombre_completo)');
            $table->string('worker_vat', 20)->comment('DNI / documento del trabajador en el momento de la planilla (snapshot de vat)');

            // ─────────────────────────────────────────────────────────
            // DATOS DEL PERÍODO QUE PUEDEN VARIAR MES A MES
            // (snapshot por si cambia respecto a la ficha del trabajador)
            // ─────────────────────────────────────────────────────────
            $table->string('cost_center', 50)->nullable()->comment('Centro de costo del período, ej. "200 ADMINISTRACION" (CENT COSTO)');
            $table->string('status', 20)->default('Activo')->comment('Estado en el período: Activo / Cesado (ESTADO)');
            $table->string('occupation')->nullable()->comment('Ocupación / cargo en el período (OCUPACION)');
            $table->decimal('monthly_salary', 12, 2)->default(0)->comment('Remuneración mensual pactada usada en el período (REMUNER. MENSUAL)');
            $table->string('afp_affiliation', 50)->nullable()->comment('Sistema previsional del período: AFP y comisión, u "ONP" (AFILIACION A AFP)');
            $table->boolean('has_family_allowance')->default(false)->comment('Tiene derecho a asignación familiar (ASIG. FAMILIAR)');
            $table->boolean('has_essalud_vida')->default(false)->comment('Está afiliado a EsSalud + Vida (ESSALUD VIDA)');

            // ─────────────────────────────────────────────────────────
            // DÍAS PARA LA BOLETA
            // ─────────────────────────────────────────────────────────
            $table->decimal('days_worked', 5, 1)->default(0)->comment('Días laborados para la boleta = 30 - días no trabajados (DIAS LABORADOS)');
            $table->decimal('days_vacation', 5, 1)->default(0)->comment('Días de vacaciones gozadas (Dias VAC)');
            $table->decimal('days_medical_rest', 5, 1)->default(0)->comment('Primeros 20 días de descanso médico (20 Prim Dias D.M)');
            $table->decimal('days_absence', 5, 1)->default(0)->comment('Faltas / sanciones (Faltas/Sanc)');
            $table->decimal('days_leave_unpaid', 5, 1)->default(0)->comment('Permisos sin goce de haber (Permisos sin Goce)');
            $table->decimal('days_leave_paid', 5, 1)->default(0)->comment('Permisos con goce de haber (Permisos con Goce)');
            $table->decimal('days_subsidy', 5, 1)->default(0)->comment('Días de subsidio (subsidio)');
            $table->decimal('days_not_worked', 5, 1)->default(0)->comment('Total días no laborados (DIAS NO LABORADOS)');
            $table->decimal('days_effective', 5, 1)->default(0)->comment('Días efectivos laborados (DIAS EFECTIVO LABORADOS)');
            $table->decimal('normal_hours', 6, 2)->default(0)->comment('Horas normales del período (HORAS NORMALES)');
            $table->boolean('has_vacation')->default(false)->comment('Indica si el trabajador tiene vacaciones en el período (TIENE VACACIONES?)');
            $table->boolean('has_subsidy')->default(false)->comment('Indica si el trabajador tiene subsidio en el período (TIENE SUBSIDIO?)');
            $table->decimal('calc_days_worked', 5, 1)->default(0)->comment('Días laborados usados para el cálculo de planilla (DIAS LABORADOS - cálculo)');
            $table->decimal('calc_days_not_worked', 5, 1)->default(0)->comment('Días no laborados usados para el cálculo de planilla (DIAS NO LABORADOS - cálculo)');

            // ─────────────────────────────────────────────────────────
            // INGRESOS / REMUNERACIONES - códigos PLAME
            // ─────────────────────────────────────────────────────────
            $table->decimal('basic_salary', 12, 2)->default(0)->comment('Remuneración básica - cód. 0121 (REMUNER BASICA)');
            $table->decimal('family_allowance', 12, 2)->default(0)->comment('Asignación familiar - cód. 0201 (ASIG. FAMILIAR)');
            $table->decimal('overtime_25', 12, 2)->default(0)->comment('Horas extras al 25% - cód. 0105 (HORAS EXTRAS 25%)');
            $table->decimal('overtime_35', 12, 2)->default(0)->comment('Horas extras al 35% - cód. 0106 (HORAS EXTRAS 35%)');
            $table->decimal('subsidy_disability', 12, 2)->default(0)->comment('Subsidio por incapacidad - cód. 0915/0916 (SUBSIDIO INCAPACIDAD)');
            $table->decimal('work_conditions', 12, 2)->default(0)->comment('Condiciones de trabajo - cód. 0917 (CONDICIONES DE TRABAJO)');
            $table->decimal('vacation_pay', 12, 2)->default(0)->comment('Remuneración vacacional - cód. 0118 (REMUNERACION VACACIONAL)');
            $table->decimal('production_bonus', 12, 2)->default(0)->comment('Bono por producción - cód. 1005/1006 (BONO POR PRODUCCION)');
            $table->decimal('holiday_days_pay', 12, 2)->default(0)->comment('Pago por días feriado - cód. 0107 (DIAS FERIADO)');
            $table->decimal('worked_rest_days_pay', 12, 2)->default(0)->comment('Pago por días de descanso trabajados (DIAS DE DESCANSO TRABAJADOS)');
            $table->decimal('night_bonus', 12, 2)->default(0)->comment('Bonificación nocturna - cód. 1008/1009 (BONIFICACION NOCTURNA)');
            $table->decimal('commercial_bonus', 12, 2)->default(0)->comment('Bono comercial - cód. 1001 (BONO COMERCIAL)');
            $table->decimal('schooling_allowance', 12, 2)->default(0)->comment('Escolaridad (ESCOLARIDAD)');
            $table->decimal('food_benefit', 12, 2)->default(0)->comment('Prestación alimentaria (PRESTACION ALIMENTARIA)');
            $table->decimal('total_income', 12, 2)->default(0)->comment('Total remuneraciones del período (TOTAL REMUN.)');

            // ─────────────────────────────────────────────────────────
            // LIQUIDACIÓN BB.SS - conceptos truncos
            // ─────────────────────────────────────────────────────────
            $table->decimal('cts_truncated', 12, 2)->default(0)->comment('CTS trunca - cód. 0904 (CTS TRUNC)');
            $table->decimal('gratification', 12, 2)->default(0)->comment('Gratificación - cód. 0407 (GRATIFICACION)');
            $table->decimal('extraordinary_bonus', 12, 2)->default(0)->comment('Bonificación extraordinaria 9% s/ gratificación - cód. 0313 (BONIFIC.EXTRAORD)');
            $table->decimal('vacation_truncated', 12, 2)->default(0)->comment('Vacaciones truncas - cód. 0114 (VACACIONES TRUNC)');

            // ─────────────────────────────────────────────────────────
            // DESCUENTOS AL TRABAJADOR - códigos PLAME
            // ─────────────────────────────────────────────────────────
            $table->decimal('onp_deduction', 12, 2)->default(0)->comment('Aporte ONP 13% - cód. 0607 (ONP)');
            $table->decimal('bonus_referral', 12, 2)->default(0)->comment('Bono "trae a tu pata" (BONO TRAE A TU PATA)');
            $table->decimal('afp_mandatory', 12, 2)->default(0)->comment('Aporte obligatorio AFP - cód. 0608 (A/OBL)');
            $table->decimal('afp_insurance', 12, 2)->default(0)->comment('Prima de seguro AFP - cód. 0606 (SEG)');
            $table->decimal('afp_commission', 12, 2)->default(0)->comment('Comisión variable AFP - cód. 0601 (COM. V.)');
            $table->decimal('afp_total', 12, 2)->default(0)->comment('Total descuento AFP = aporte + seguro + comisión (TOTAL AFP)');
            $table->decimal('income_tax_5th', 12, 2)->default(0)->comment('Renta de 5ta categoría - cód. 0605 (RENTA 5a. CATEG.)');
            $table->decimal('oncosalud_plan', 12, 2)->default(0)->comment('Plan de salud Oncosalud/Fesalud - cód. 0701 (ONCOSALUD-PLAN SALUD)');
            $table->decimal('advances_loans', 12, 2)->default(0)->comment('Adelantos / préstamos / otros - cód. 0701 (ADELANTOS/PREST/LBS/OTROS)');
            $table->decimal('other_deductions', 12, 2)->default(0)->comment('Otros descuentos - cód. 0706 (OTROS DSCTOS)');
            $table->decimal('judicial_deductions', 12, 2)->default(0)->comment('Descuentos judiciales - cód. 0703 (DESCUENTOS JUDICIALES)');
            $table->decimal('grace_amount', 12, 2)->default(0)->comment('Suma graciosa (Suma graciosa)');
            $table->decimal('total_deductions', 12, 2)->default(0)->comment('Total de descuentos al trabajador (TOTAL DSCTOS)');

            // ─────────────────────────────────────────────────────────
            // NETOS Y CONCEPTOS DE FIN DE AÑO
            // ─────────────────────────────────────────────────────────
            $table->decimal('net_pay_preliminary', 12, 2)->default(0)->comment('Neto a pagar preliminar (NETO A PAGAR-P)');
            $table->decimal('christmas_gratification', 12, 2)->default(0)->comment('Gratificación por navidad - cód. 0406 (GRATIFICACION X NAVIDAD)');
            $table->decimal('christmas_extraordinary_bonus', 12, 2)->default(0)->comment('Bonificación extraordinaria 9% s/ grati navidad - cód. 0312 (Bonif. Extraord. 9%)');
            $table->decimal('aguinaldo', 12, 2)->default(0)->comment('Aguinaldos: canastas y juguetes - cód. 0903 (AGUINALDOS)');
            $table->decimal('net_pay_plus_aguinaldo', 12, 2)->default(0)->comment('Neto a pagar + aguinaldos (Neto pagar + aguinaldos)');

            // ─────────────────────────────────────────────────────────
            // APORTES DEL EMPLEADOR - códigos PLAME
            // ─────────────────────────────────────────────────────────
            $table->decimal('cts_employer', 12, 2)->default(0)->comment('CTS aporte del empleador - cód. 0904 (CTS)');
            $table->decimal('essalud_employer', 12, 2)->default(0)->comment('Aporte EsSalud 9% del empleador (ESSALUD)');
            $table->decimal('sctr_total', 12, 2)->default(0)->comment('SCTR total (salud + pensión) (SCTR)');
            $table->decimal('life_insurance', 12, 2)->default(0)->comment('Seguro Vida Ley - cód. 0803 (SEGURO VIDA LEY)');
            $table->decimal('sctr_health', 12, 2)->default(0)->comment('SCTR salud - cód. 0810 (SCTR SALUD)');
            $table->decimal('sctr_pension', 12, 2)->default(0)->comment('SCTR pensión - cód. 0814 (SCTR PENSION)');
            $table->decimal('employer_contributions_total', 12, 2)->default(0)->comment('Total aportes del empleador (TOTAL)');

            // ─────────────────────────────────────────────────────────
            // NETOS FINALES
            // ─────────────────────────────────────────────────────────
            $table->decimal('vacation_paid_preliminary', 12, 2)->default(0)->comment('Vacaciones pagadas - preliminar (vacac pagadas -preliminar)');
            $table->decimal('net_pay_final', 12, 2)->default(0)->comment('Neto a pagar final = neto preliminar - vacaciones pagadas (NETO A PAGAR FINAL)');
            $table->decimal('worker_deduction_total', 12, 2)->default(0)->comment('Descuento total del trabajador (ONP/AFP + renta 5ta) (Dscto trabajador)');

            $table->timestamps();

            // Una sola fila por trabajador por período
            $table->unique(['period_id', 'worker_id'], 'gh_payroll_register_period_worker_unique');
            $table->index(['period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_payroll_register');
    }
};
