<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    // Per-day schedule overrides (e.g. Saturday times, or Domenica's per-day variation)
    Schema::create('work_schedule_details', function (Blueprint $table) {
      $table->id();
      $table->foreignId('work_schedule_id')->constrained('work_schedules')->cascadeOnDelete();
      // MySQL DAYOFWEEK: 1=Sunday, 2=Monday, ..., 7=Saturday
      $table->tinyInteger('day_of_week')->unsigned();
      $table->time('checkin');
      $table->time('lunch_out')->nullable();
      $table->time('lunch_in')->nullable();
      $table->time('checkout');
      $table->unique(['work_schedule_id', 'day_of_week']);
    });

    // Update schedule 1 (was "Jornada Estándar" → "Normal")
    // Base row stores Mon–Fri default times
    DB::table('work_schedules')->where('id', 1)->update([
      'name'      => 'Normal',
      'checkin'   => '08:00:00',
      'lunch_out' => '13:00:00',
      'lunch_in'  => '14:25:00',
      'checkout'  => '18:00:00',
    ]);

    // Insert schedules 2–12 (base = Mon–Fri times)
    DB::table('work_schedules')->insert([
      ['id' => 2,  'name' => 'Comercial',           'checkin' => '08:30:00', 'lunch_out' => '13:00:00', 'lunch_in' => '15:00:00', 'checkout' => '19:00:00'],
      ['id' => 3,  'name' => 'De 8:30am a 6:30pm',  'checkin' => '08:30:00', 'lunch_out' => '13:00:00', 'lunch_in' => '14:25:00', 'checkout' => '18:30:00'],
      ['id' => 4,  'name' => 'De 6:30am a 6pm',     'checkin' => '06:30:00', 'lunch_out' => '11:30:00', 'lunch_in' => '14:30:00', 'checkout' => '18:00:00'],
      ['id' => 5,  'name' => 'Normal + Sabado',      'checkin' => '08:00:00', 'lunch_out' => '13:00:00', 'lunch_in' => '14:25:00', 'checkout' => '18:00:00'],
      ['id' => 6,  'name' => 'Comercial + Sabado',   'checkin' => '08:30:00', 'lunch_out' => '13:00:00', 'lunch_in' => '15:00:00', 'checkout' => '19:00:00'],
      ['id' => 7,  'name' => 'De 08:30am a 7:28pm', 'checkin' => '08:30:00', 'lunch_out' => '13:00:00', 'lunch_in' => '15:30:00', 'checkout' => '19:28:00'],
      ['id' => 8,  'name' => 'De 07:30am a 6pm',    'checkin' => '07:30:00', 'lunch_out' => '12:30:00', 'lunch_in' => '14:30:00', 'checkout' => '18:00:00'],
      ['id' => 9,  'name' => 'De 8:30am a 7:30pm',  'checkin' => '08:30:00', 'lunch_out' => '13:00:00', 'lunch_in' => '15:30:00', 'checkout' => '19:30:00'],
      ['id' => 10, 'name' => 'De 7:30am a 5pm',     'checkin' => '07:30:00', 'lunch_out' => '13:00:00', 'lunch_in' => '14:00:00', 'checkout' => '17:00:00'],
      // Schedule 11: base = Lunes values; Wed & Fri overrides stored in work_schedule_details
      ['id' => 11, 'name' => 'Domenica',             'checkin' => '08:15:00', 'lunch_out' => '12:45:00', 'lunch_in' => '15:00:00', 'checkout' => '18:30:00'],
      ['id' => 12, 'name' => 'Normal DP',            'checkin' => '08:00:00', 'lunch_out' => '13:00:00', 'lunch_in' => '14:25:00', 'checkout' => '18:00:00'],
    ]);

    // Saturday overrides for all 12 schedules (day_of_week = 7, no lunch on Saturday)
    $saturdays = [
      ['work_schedule_id' => 1,  'day_of_week' => 7, 'checkin' => '08:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '13:00:00'],
      ['work_schedule_id' => 2,  'day_of_week' => 7, 'checkin' => '08:30:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '13:30:00'],
      ['work_schedule_id' => 3,  'day_of_week' => 7, 'checkin' => '08:30:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '13:30:00'],
      ['work_schedule_id' => 4,  'day_of_week' => 7, 'checkin' => '07:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '12:30:00'],
      ['work_schedule_id' => 5,  'day_of_week' => 7, 'checkin' => '08:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '18:00:00'],
      ['work_schedule_id' => 6,  'day_of_week' => 7, 'checkin' => '09:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '14:30:00'],
      ['work_schedule_id' => 7,  'day_of_week' => 7, 'checkin' => '09:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '14:40:00'],
      ['work_schedule_id' => 8,  'day_of_week' => 7, 'checkin' => '07:30:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '13:00:00'],
      ['work_schedule_id' => 9,  'day_of_week' => 7, 'checkin' => '09:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '14:30:00'],
      ['work_schedule_id' => 10, 'day_of_week' => 7, 'checkin' => '07:30:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '13:00:00'],
      ['work_schedule_id' => 11, 'day_of_week' => 7, 'checkin' => '08:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '13:00:00'],
      ['work_schedule_id' => 12, 'day_of_week' => 7, 'checkin' => '08:00:00', 'lunch_out' => null, 'lunch_in' => null, 'checkout' => '13:00:00'],
    ];

    // Schedule 11 (Domenica) per-day overrides: Wednesday and Friday differ from Mon base
    $domenicaOverrides = [
      // Miércoles (day_of_week = 4): checkin 08:30, lunch_out 13:00 (rest same as base)
      ['work_schedule_id' => 11, 'day_of_week' => 4, 'checkin' => '08:30:00', 'lunch_out' => '13:00:00', 'lunch_in' => '15:00:00', 'checkout' => '18:30:00'],
      // Viernes (day_of_week = 6): lunch_out 13:00, checkout 18:15 (rest same as base)
      ['work_schedule_id' => 11, 'day_of_week' => 6, 'checkin' => '08:15:00', 'lunch_out' => '13:00:00', 'lunch_in' => '15:00:00', 'checkout' => '18:15:00'],
    ];

    DB::table('work_schedule_details')->insert(array_merge($saturdays, $domenicaOverrides));
  }

  public function down(): void
  {
    Schema::dropIfExists('work_schedule_details');
    DB::table('work_schedules')->whereIn('id', range(2, 12))->delete();
    DB::table('work_schedules')->where('id', 1)->update([
      'name'      => 'Jornada Estándar',
      'checkin'   => '08:00:00',
      'lunch_out' => '13:00:00',
      'lunch_in'  => '14:24:00',
      'checkout'  => '18:00:00',
    ]);
  }
};
