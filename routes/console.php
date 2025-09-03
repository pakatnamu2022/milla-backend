<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:snapshot-assign-sede')
  ->monthlyOn(1, '00:05')
  ->description('Generar snapshot mensual de asignaciones de asesores de sede');
