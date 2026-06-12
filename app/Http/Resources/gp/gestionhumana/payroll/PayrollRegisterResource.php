<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                           => $this->id,
            'period_id'                    => $this->period_id,
            'worker_id'                    => $this->worker_id,
            'worker_name'                  => $this->worker_name,
            'worker_vat'                   => $this->worker_vat,
            // Datos del período
            'cost_center'                  => $this->cost_center,
            'status'                       => $this->status,
            'occupation'                   => $this->occupation,
            'monthly_salary'               => $this->monthly_salary,
            'afp_affiliation'              => $this->afp_affiliation,
            'has_family_allowance'         => $this->has_family_allowance,
            'has_essalud_vida'             => $this->has_essalud_vida,
            // Días
            'days_worked'                  => $this->days_worked,
            'days_vacation'                => $this->days_vacation,
            'days_medical_rest'            => $this->days_medical_rest,
            'days_absence'                 => $this->days_absence,
            'days_leave_unpaid'            => $this->days_leave_unpaid,
            'days_leave_paid'              => $this->days_leave_paid,
            'days_subsidy'                 => $this->days_subsidy,
            'days_not_worked'              => $this->days_not_worked,
            'days_effective'               => $this->days_effective,
            'normal_hours'                 => $this->normal_hours,
            'has_vacation'                 => $this->has_vacation,
            'has_subsidy'                  => $this->has_subsidy,
            'calc_days_worked'             => $this->calc_days_worked,
            'calc_days_not_worked'         => $this->calc_days_not_worked,
            // Ingresos
            'basic_salary'                 => $this->basic_salary,
            'family_allowance'             => $this->family_allowance,
            'overtime_25'                  => $this->overtime_25,
            'overtime_35'                  => $this->overtime_35,
            'subsidy_disability'           => $this->subsidy_disability,
            'work_conditions'              => $this->work_conditions,
            'vacation_pay'                 => $this->vacation_pay,
            'production_bonus'             => $this->production_bonus,
            'holiday_days_pay'             => $this->holiday_days_pay,
            'worked_rest_days_pay'         => $this->worked_rest_days_pay,
            'night_bonus'                  => $this->night_bonus,
            'commercial_bonus'             => $this->commercial_bonus,
            'schooling_allowance'          => $this->schooling_allowance,
            'food_benefit'                 => $this->food_benefit,
            'total_income'                 => $this->total_income,
            // BB.SS truncos
            'cts_truncated'                => $this->cts_truncated,
            'gratification'                => $this->gratification,
            'extraordinary_bonus'          => $this->extraordinary_bonus,
            'vacation_truncated'           => $this->vacation_truncated,
            // Descuentos
            'onp_deduction'                => $this->onp_deduction,
            'bonus_referral'               => $this->bonus_referral,
            'afp_mandatory'                => $this->afp_mandatory,
            'afp_insurance'                => $this->afp_insurance,
            'afp_commission'               => $this->afp_commission,
            'afp_total'                    => $this->afp_total,
            'income_tax_5th'               => $this->income_tax_5th,
            'oncosalud_plan'               => $this->oncosalud_plan,
            'advances_loans'               => $this->advances_loans,
            'other_deductions'             => $this->other_deductions,
            'judicial_deductions'          => $this->judicial_deductions,
            'grace_amount'                 => $this->grace_amount,
            'total_deductions'             => $this->total_deductions,
            // Netos y fin de año
            'net_pay_preliminary'          => $this->net_pay_preliminary,
            'christmas_gratification'      => $this->christmas_gratification,
            'christmas_extraordinary_bonus'=> $this->christmas_extraordinary_bonus,
            'aguinaldo'                    => $this->aguinaldo,
            'net_pay_plus_aguinaldo'       => $this->net_pay_plus_aguinaldo,
            // Aportes empleador
            'cts_employer'                 => $this->cts_employer,
            'essalud_employer'             => $this->essalud_employer,
            'sctr_total'                   => $this->sctr_total,
            'life_insurance'               => $this->life_insurance,
            'sctr_health'                  => $this->sctr_health,
            'sctr_pension'                 => $this->sctr_pension,
            'employer_contributions_total' => $this->employer_contributions_total,
            // Netos finales
            'vacation_paid_preliminary'    => $this->vacation_paid_preliminary,
            'net_pay_final'                => $this->net_pay_final,
            'worker_deduction_total'       => $this->worker_deduction_total,
            // Relaciones
            'period'                       => $this->whenLoaded('period'),
        ];
    }
}
