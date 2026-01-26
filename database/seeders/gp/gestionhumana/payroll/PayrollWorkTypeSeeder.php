<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollWorkType;
use App\Models\gp\gestionhumana\payroll\PayrollWorkTypeSegment;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollWorkTypeSeeder"
 */
class PayrollWorkTypeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // STEP 1: Delete all existing work types (cascade will delete segments)
    // Temporarily disable foreign key checks to allow deletion
    \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    PayrollWorkType::query()->forceDelete();
    \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    // STEP 2: Create DT (Day Shift 7am-7pm) with segments
    $dt = PayrollWorkType::create([
      'code' => 'DT',
      'name' => 'Day Shift',
      'description' => 'Day shift 7am-7pm with configurable segments',
      'multiplier' => 1.0000, // Deprecated but maintained
      'base_hours' => 12,
      'shift_start_time' => '07:00:00',
      'shift_duration_hours' => 12.00,
      'nocturnal_base_multiplier' => 1.0000,
      'is_extra_hours' => false,
      'is_night_shift' => false,
      'is_holiday' => false,
      'is_sunday' => false,
      'active' => true,
      'order' => 1,
    ]);

    // DT Segments
    PayrollWorkTypeSegment::create([
      'work_type_id' => $dt->id,
      'segment_type' => 'WORK',
      'segment_order' => 1,
      'duration_hours' => 8.00,
      'multiplier' => 1.0000,
      'description' => 'Normal: 7am-3pm (8h × 1.0)',
    ]);

    PayrollWorkTypeSegment::create([
      'work_type_id' => $dt->id,
      'segment_type' => 'WORK',
      'segment_order' => 2,
      'duration_hours' => 2.00,
      'multiplier' => 1.2500,
      'description' => 'OT 25%: 3pm-5pm (2h × 1.25)',
    ]);

    PayrollWorkTypeSegment::create([
      'work_type_id' => $dt->id,
      'segment_type' => 'WORK',
      'segment_order' => 3,
      'duration_hours' => 2.00,
      'multiplier' => 1.3500,
      'description' => 'OT 35%: 5pm-7pm (2h × 1.35)',
    ]);

    PayrollWorkTypeSegment::create([
      'work_type_id' => $dt->id,
      'segment_type' => 'BREAK',
      'segment_order' => 4,
      'duration_hours' => 0.75, // 45 minutes
      'multiplier' => null,
      'description' => 'Almuerzo: 1pm-1:45pm (45min)',
    ]);

    // STEP 3: Create NT (Night Shift 7pm-7am) with segments
    $nt = PayrollWorkType::create([
      'code' => 'NT',
      'name' => 'Night Shift',
      'description' => 'Night shift 7pm-7am with nocturnal base',
      'multiplier' => 1.3500, // Deprecated
      'base_hours' => 12,
      'shift_start_time' => '19:00:00',
      'shift_duration_hours' => 12.00,
      'nocturnal_base_multiplier' => 1.3500, // Applied to ALL segments
      'is_extra_hours' => false,
      'is_night_shift' => true,
      'is_holiday' => false,
      'is_sunday' => false,
      'active' => true,
      'order' => 2,
    ]);

    // NT Segments (nocturnal_base applied to all)
    PayrollWorkTypeSegment::create([
      'work_type_id' => $nt->id,
      'segment_type' => 'WORK',
      'segment_order' => 1,
      'duration_hours' => 8.00,
      'multiplier' => 1.0000, // Effective: 1.35 × 1.0 = 1.35
      'description' => 'Nocturno normal: 7pm-3am (8h × 1.35)',
    ]);

    PayrollWorkTypeSegment::create([
      'work_type_id' => $nt->id,
      'segment_type' => 'WORK',
      'segment_order' => 2,
      'duration_hours' => 2.00,
      'multiplier' => 1.2500, // Effective: 1.35 × 1.25 = 1.6875
      'description' => 'Nocturno OT 25%: 3am-5am (2h × 1.6875)',
    ]);

    PayrollWorkTypeSegment::create([
      'work_type_id' => $nt->id,
      'segment_type' => 'WORK',
      'segment_order' => 3,
      'duration_hours' => 2.00,
      'multiplier' => 1.3500, // Effective: 1.35 × 1.35 = 1.8225
      'description' => 'Nocturno OT 35%: 5am-7am (2h × 1.8225)',
    ]);

    PayrollWorkTypeSegment::create([
      'work_type_id' => $nt->id,
      'segment_type' => 'BREAK',
      'segment_order' => 4,
      'duration_hours' => 0.75,
      'multiplier' => null,
      'description' => 'Cena: 9pm-9:45pm (45min)',
    ]);
  }
}
