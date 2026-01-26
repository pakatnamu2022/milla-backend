<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollConcept;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollConceptSeeder"
 */
class PayrollConceptSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $concepts = [
      // EARNINGS - SEGMENTED
      [
        'code' => 'WORK_EARNINGS',
        'name' => 'Work Earnings (Segmented)',
        'description' => 'Total earnings from segmented work type calculation',
        'type' => 'EARNING',
        'category' => 'BASE_SALARY',
        'formula' => 'SEGMENTED_TOTAL_EARNINGS',
        'formula_description' => 'Sum of all segmented work type earnings (DT, NT, etc)',
        'is_taxable' => true,
        'calculation_order' => 10,
        'active' => true,
      ],
      [
        'code' => 'BASIC_SALARY',
        'name' => 'Basic Salary (Legacy)',
        'description' => 'Base salary proportional to days worked - DEPRECATED',
        'type' => 'EARNING',
        'category' => 'BASE_SALARY',
        'formula' => 'SUELDO * DAYS_WORKED / 30',
        'formula_description' => 'Sueldo x Days Worked / 30',
        'is_taxable' => true,
        'calculation_order' => 11,
        'active' => false, // Disabled - replaced by WORK_EARNINGS
      ],
      [
        'code' => 'OVERTIME_25',
        'name' => 'Overtime 25% (Legacy)',
        'description' => 'First 2 hours of daily overtime - DEPRECATED',
        'type' => 'EARNING',
        'category' => 'OVERTIME',
        'formula' => 'EXTRA_HOURS_25 * HOURLY_RATE * OVERTIME_25_RATE',
        'formula_description' => 'Extra Hours 25% x Hourly Rate x 1.25',
        'is_taxable' => true,
        'calculation_order' => 20,
        'active' => false, // Disabled - now handled by segments
      ],
      [
        'code' => 'OVERTIME_35',
        'name' => 'Overtime 35% (Legacy)',
        'description' => 'Overtime beyond 2 hours - DEPRECATED',
        'type' => 'EARNING',
        'category' => 'OVERTIME',
        'formula' => 'EXTRA_HOURS_35 * HOURLY_RATE * OVERTIME_35_RATE',
        'formula_description' => 'Extra Hours 35% x Hourly Rate x 1.35',
        'is_taxable' => true,
        'calculation_order' => 21,
        'active' => false, // Disabled - now handled by segments
      ],
      [
        'code' => 'NIGHT_BONUS',
        'name' => 'Night Shift Bonus (Legacy)',
        'description' => 'Additional pay for night work - DEPRECATED',
        'type' => 'EARNING',
        'category' => 'BONUSES',
        'formula' => 'NIGHT_HOURS * HOURLY_RATE * NIGHT_BONUS_RATE',
        'formula_description' => 'Night Hours x Hourly Rate x 35%',
        'is_taxable' => true,
        'calculation_order' => 30,
        'active' => false, // Disabled - now handled by segments
      ],
      [
        'code' => 'HOLIDAY_PAY',
        'name' => 'Holiday Pay (Legacy)',
        'description' => 'Pay for work on holidays - DEPRECATED',
        'type' => 'EARNING',
        'category' => 'BONUSES',
        'formula' => 'HOLIDAY_HOURS * HOURLY_RATE * HOLIDAY_RATE',
        'formula_description' => 'Holiday Hours x Hourly Rate x 2',
        'is_taxable' => true,
        'calculation_order' => 31,
        'active' => false, // Disabled - will be handled by work type segments when needed
      ],
      [
        'code' => 'FOOD_ALLOWANCE',
        'name' => 'Food Allowance',
        'description' => 'Daily food allowance',
        'type' => 'EARNING',
        'category' => 'ALLOWANCES',
        'formula' => 'DAYS_WORKED * 15',
        'formula_description' => 'Days Worked x S/ 15.00',
        'is_taxable' => false,
        'calculation_order' => 40,
        'active' => true,
      ],

      // DEDUCTIONS
      [
        'code' => 'AFP_CONTRIBUTION',
        'name' => 'AFP Contribution',
        'description' => 'Pension fund contribution',
        'type' => 'DEDUCTION',
        'category' => 'SOCIAL_SECURITY',
        'formula' => 'WORK_EARNINGS * AFP_RATE',
        'formula_description' => 'Work Earnings x 10%',
        'is_taxable' => false,
        'calculation_order' => 100,
        'active' => true,
      ],
      [
        'code' => 'INCOME_TAX',
        'name' => 'Income Tax (5th Category)',
        'description' => 'Monthly income tax withholding',
        'type' => 'DEDUCTION',
        'category' => 'TAXES',
        'formula' => 'IF(WORK_EARNINGS > UIT * 7 / 12, (WORK_EARNINGS - UIT * 7 / 12) * 0.08, 0)',
        'formula_description' => 'Progressive tax calculation',
        'is_taxable' => false,
        'calculation_order' => 110,
        'active' => true,
      ],
      [
        'code' => 'ABSENT_DISCOUNT',
        'name' => 'Absent Days Discount',
        'description' => 'Discount for unexcused absences',
        'type' => 'DEDUCTION',
        'category' => 'OTHER_DEDUCTIONS',
        'formula' => 'DAYS_ABSENT * DAILY_RATE',
        'formula_description' => 'Days Absent x Daily Rate',
        'is_taxable' => false,
        'calculation_order' => 120,
        'active' => false,
      ],

      // EMPLOYER CONTRIBUTIONS
      [
        'code' => 'ESSALUD',
        'name' => 'EsSalud',
        'description' => 'Employer health insurance contribution',
        'type' => 'EMPLOYER_CONTRIBUTION',
        'category' => 'EMPLOYER_TAXES',
        'formula' => 'WORK_EARNINGS * ESSALUD_RATE',
        'formula_description' => 'Work Earnings x 9%',
        'is_taxable' => false,
        'calculation_order' => 200,
        'active' => true,
      ],
      [
        'code' => 'SCTR',
        'name' => 'SCTR',
        'description' => 'High-risk work insurance',
        'type' => 'EMPLOYER_CONTRIBUTION',
        'category' => 'EMPLOYER_TAXES',
        'formula' => 'WORK_EARNINGS * 0.0153',
        'formula_description' => 'Work Earnings x 1.53%',
        'is_taxable' => false,
        'calculation_order' => 210,
        'active' => true,
      ],
      [
        'code' => 'LIFE_INSURANCE',
        'name' => 'Life Insurance',
        'description' => 'Employer life insurance (Vida Ley)',
        'type' => 'EMPLOYER_CONTRIBUTION',
        'category' => 'EMPLOYER_TAXES',
        'formula' => 'WORK_EARNINGS * 0.0053',
        'formula_description' => 'Work Earnings x 0.53%',
        'is_taxable' => false,
        'calculation_order' => 220,
        'active' => true,
      ],

      // INFORMATIVE
      [
        'code' => 'CTS_PROVISION',
        'name' => 'CTS Provision',
        'description' => 'Monthly CTS provision (informative)',
        'type' => 'INFO',
        'category' => 'INFORMATIVE',
        'formula' => 'WORK_EARNINGS * CTS_RATE',
        'formula_description' => 'Work Earnings x 8.33%',
        'is_taxable' => false,
        'calculation_order' => 300,
        'active' => true,
      ],
      [
        'code' => 'GRAT_PROVISION',
        'name' => 'Gratification Provision',
        'description' => 'Monthly gratification provision (informative)',
        'type' => 'INFO',
        'category' => 'INFORMATIVE',
        'formula' => 'WORK_EARNINGS * GRATIFICATION_RATE',
        'formula_description' => 'Work Earnings x 16.67%',
        'is_taxable' => false,
        'calculation_order' => 310,
        'active' => true,
      ],
      [
        'code' => 'VACATION_PROVISION',
        'name' => 'Vacation Provision',
        'description' => 'Monthly vacation provision (informative)',
        'type' => 'INFO',
        'category' => 'INFORMATIVE',
        'formula' => 'WORK_EARNINGS * VACATIONS_RATE',
        'formula_description' => 'Work Earnings x 8.33%',
        'is_taxable' => false,
        'calculation_order' => 320,
        'active' => true,
      ],
    ];

    foreach ($concepts as $concept) {
      PayrollConcept::updateOrCreate(
        ['code' => $concept['code']],
        $concept
      );
    }
  }
}
