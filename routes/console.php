<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar los 4 comandos para ejecutarse el 1ro de cada mes
Schedule::command('app:snapshot-assign-brand-consultant')
  ->monthlyOn(1, '00:01')
  ->timezone('America/Lima');

Schedule::command('app:snapshot-assign-company-branch-periods')
  ->monthlyOn(1, '00:05')
  ->timezone('America/Lima');

Schedule::command('app:snapshot-assignment-leadership-periods')
  ->monthlyOn(1, '00:10')
  ->timezone('America/Lima');

Schedule::command('app:snapshot-commercial-manager-brand-group-periods')
  ->monthlyOn(1, '00:15')
  ->timezone('America/Lima');

Schedule::command('app:sync-exchange-rate')
  ->everyFiveMinutes()
  ->between('8:00', '18:00')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->before(function () {
    Log::info('🕐 Iniciando sincronización de tasa de cambio', [
      'timestamp' => now()->toDateTimeString(),
    ]);
  })
  ->onSuccess(function () {
    Log::info('✅ Sincronización de tasa de cambio completada exitosamente', [
      'timestamp' => now()->toDateTimeString(),
    ]);
  })
  ->onFailure(function () {
    Log::error('❌ Error en la sincronización de tasa de cambio', [
      'timestamp' => now()->toDateTimeString(),
    ]);
  })
  ->appendOutputTo(storage_path('logs/exchange-rate-sync.log'));
