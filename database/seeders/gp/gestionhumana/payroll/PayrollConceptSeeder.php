<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollConcept;
use Illuminate\Database\Seeder;

class PayrollConceptSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $concepts = [
      // EARNINGS
      [
        'code' => 'BASIC_SALARY',
        'name' => 'Basic Salary',
        'description' => 'Base salary proportional to days worked',
        'type' => 'EARNING',
        'category' => 'BASE_SALARY',
        'formula' => 'SUELDO * DAYS_WORKED / 30',
        'formula_description' => 'Sueldo x Days Worked / 30',
        'is_taxable' => true,
        'calculation_order' => 10,
        'active' => true,
      ],
      [
        'code' => 'OVERTIME_25',
        'name' => 'Overtime 25%',
        'description' => 'First 2 hours of daily overtime',
        'type' => 'EARNING',
        'category' => 'OVERTIME',
        'formula' => 'EXTRA_HOURS_25 * HOURLY_RATE * OVERTIME_25_RATE',
        'formula_description' => 'Extra Hours 25% x Hourly Rate x 1.25',
        'is_taxable' => true,
        'calculation_order' => 20,
        'active' => true,
      ],
      [
        'code' => 'OVERTIME_35',
        'name' => 'Overtime 35%',
        'description' => 'Overtime beyond 2 hours',
        'type' => 'EARNING',
        'category' => 'OVERTIME',
        'formula' => 'EXTRA_HOURS_35 * HOURLY_RATE * OVERTIME_35_RATE',
        'formula_description' => 'Extra Hours 35% x Hourly Rate x 1.35',
        'is_taxable' => true,
        'calculation_order' => 21,
        'active' => true,
      ],
      [
        'code' => 'NIGHT_BONUS',
        'name' => 'Night Shift Bonus',
        'description' => 'Additional pay for night work',
        'type' => 'EARNING',
        'category' => 'BONUSES',
        'formula' => 'NIGHT_HOURS * HOURLY_RATE * NIGHT_BONUS_RATE',
        'formula_description' => 'Night Hours x Hourly Rate x 35%',
        'is_taxable' => true,
        'calculation_order' => 30,
        'active' => true,
      ],
      [
        'code' => 'HOLIDAY_PAY',
        'name' => 'Holiday Pay',
        'description' => 'Pay for work on holidays',
        'type' => 'EARNING',
        'category' => 'BONUSES',
        'formula' => 'HOLIDAY_HOURS * HOURLY_RATE * HOLIDAY_RATE',
        'formula_description' => 'Holiday Hours x Hourly Rate x 2',
        'is_taxable' => true,
        'calculation_order' => 31,
        'active' => true,
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
        'formula' => 'BASIC_SALARY * AFP_RATE',
        'formula_description' => 'Basic Salary x 10%',
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
        'formula' => 'IF(BASIC_SALARY > UIT * 7 / 12, (BASIC_SALARY - UIT * 7 / 12) * 0.08, 0)',
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
        'formula' => 'BASIC_SALARY * ESSALUD_RATE',
        'formula_description' => 'Basic Salary x 9%',
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
        'formula' => 'BASIC_SALARY * 0.0153',
        'formula_description' => 'Basic Salary x 1.53%',
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
        'formula' => 'BASIC_SALARY * 0.0053',
        'formula_description' => 'Basic Salary x 0.53%',
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
        'formula' => 'BASIC_SALARY * CTS_RATE',
        'formula_description' => 'Basic Salary x 8.33%',
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
        'formula' => 'BASIC_SALARY * GRATIFICATION_RATE',
        'formula_description' => 'Basic Salary x 16.67%',
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
        'formula' => 'BASIC_SALARY * VACATIONS_RATE',
        'formula_description' => 'Basic Salary x 8.33%',
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
