<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollWorkType;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollWorkTypeSeeder
 */
class PayrollWorkTypeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $workTypes = [
      [
        'code' => 'DT',
        'name' => 'Day Shift',
        'description' => 'Regular daytime work shift',
        'multiplier' => 1.0000,
        'base_hours' => 8,
        'is_extra_hours' => false,
        'is_night_shift' => false,
        'is_holiday' => false,
        'is_sunday' => false,
        'active' => true,
        'order' => 1,
      ],
      [
        'code' => 'NT',
        'name' => 'Night Shift',
        'description' => 'Night work shift (10pm - 6am)',
        'multiplier' => 1.3500,
        'base_hours' => 8,
        'is_extra_hours' => false,
        'is_night_shift' => true,
        'is_holiday' => false,
        'is_sunday' => false,
        'active' => true,
        'order' => 2,
      ],
      [
        'code' => 'DF',
        'name' => 'Holiday Shift',
        'description' => 'Work on official holidays',
        'multiplier' => 2.0000,
        'base_hours' => 8,
        'is_extra_hours' => false,
        'is_night_shift' => false,
        'is_holiday' => true,
        'is_sunday' => false,
        'active' => true,
        'order' => 3,
      ],
      [
        'code' => 'HE25',
        'name' => 'Overtime 25%',
        'description' => 'First 2 hours of overtime',
        'multiplier' => 1.2500,
        'base_hours' => 2,
        'is_extra_hours' => true,
        'is_night_shift' => false,
        'is_holiday' => false,
        'is_sunday' => false,
        'active' => true,
        'order' => 4,
      ],
      [
        'code' => 'HE35',
        'name' => 'Overtime 35%',
        'description' => 'Overtime beyond 2 hours',
        'multiplier' => 1.3500,
        'base_hours' => 1,
        'is_extra_hours' => true,
        'is_night_shift' => false,
        'is_holiday' => false,
        'is_sunday' => false,
        'active' => true,
        'order' => 5,
      ],
      [
        'code' => 'DDT',
        'name' => 'Sunday Worked',
        'description' => 'Work on Sundays',
        'multiplier' => 2.0000,
        'base_hours' => 8,
        'is_extra_hours' => false,
        'is_night_shift' => false,
        'is_holiday' => false,
        'is_sunday' => true,
        'active' => true,
        'order' => 6,
      ],
    ];

    foreach ($workTypes as $workType) {
      PayrollWorkType::updateOrCreate(
        ['code' => $workType['code']],
        $workType
      );
    }
  }
}
