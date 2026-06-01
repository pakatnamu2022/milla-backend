<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('work_schedules', function (Blueprint $table) {
      $table->id();
      $table->string('name', 100);
      $table->time('checkin')->default('08:00:00');
      $table->time('lunch_out')->default('13:00:00');
      $table->time('lunch_in')->default('14:24:00');
      $table->time('checkout')->default('18:00:00');
      $table->timestamps();
    });

    DB::table('work_schedules')->insert([
      'name'      => 'Jornada Estándar',
      'checkin'   => '08:00:00',
      'lunch_out' => '13:00:00',
      'lunch_in'  => '14:24:00',
      'checkout'  => '18:00:00',
    ]);
  }

  public function down(): void
  {
    Schema::dropIfExists('work_schedules');
  }
};
