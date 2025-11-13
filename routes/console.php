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
  ->between('8:00', '9:00')
  ->timezone('America/Lima')
  ->withoutOverlapping();
//  ->before(function () {
//    Log::info('Iniciando sincronización de tasa de cambio', [
//      'timestamp' => now()->toDateTimeString(),
//    ]);
//  })
//  ->onSuccess(function () {
//    Log::info('Sincronización de tasa de cambio completada exitosamente', [
//      'timestamp' => now()->toDateTimeString(),
//    ]);
//  })
//  ->onFailure(function () {
//    Log::error('Error en la sincronización de tasa de cambio', [
//      'timestamp' => now()->toDateTimeString(),
//    ]);
//  })
//  ->appendOutputTo(storage_path('logs/exchange-rate-sync.log'));

// Verificar y migrar órdenes de compra de vehículos pendientes
Schedule::command('po:verify-migration --all')
  ->everyThirtySeconds()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar y migrar guías de remisión pendientes
Schedule::command('shipping-guide:verify-migration --all')
  ->everyThirtySeconds()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar invoice_dynamics desde Dynamics
Schedule::command('po:sync-invoice-dynamics --all')
  ->everyThirtySeconds()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar credit_note_dynamics desde Dynamics
Schedule::command('po:sync-credit-note-dynamics --all')
  ->everyThirtySeconds()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar y sincronizar documentos electrónicos de venta a Dynamics
Schedule::command('electronic-document:verify-sync --all')
  ->everyFiveSeconds()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// DESHABILITADO: Ya no se usa, el reenvío se hace desde frontend con endpoint /resend
// Schedule::command('po:process-credit-note-updates')
//   ->everyMinute()
//   ->timezone('America/Lima')
//   ->withoutOverlapping()
//   ->runInBackground();

// Consultar estado de documentos electrónicos enviados a SUNAT
Schedule::command('app:check-pending-electronic-documents')
  ->everyFiveSeconds()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();
