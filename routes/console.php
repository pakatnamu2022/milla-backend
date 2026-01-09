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

// Verificar y migrar órdenes de compra de vehículos pendientes
// Procesa lotes de 100 órdenes cada 5 minutos (1,200/hora) para evitar sobrecarga
Schedule::command('po:verify-migration --all --limit=100')
  ->everyFiveMinutes()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar y migrar guías de remisión pendientes
// Procesa lotes de 100 guías cada 5 minutos (1,200/hora) para evitar sobrecarga
Schedule::command('shipping-guide:verify-migration --all --limit=100')
  ->everyFiveMinutes()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar invoice_dynamics desde Dynamics
// Procesa lotes de 50 órdenes cada 10 minutos (300/hora) para reducir carga en API
Schedule::command('po:sync-invoice-dynamics --all --limit=50')
  ->everyTenMinutes()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar credit_note_dynamics desde Dynamics
// Procesa lotes de 50 órdenes cada 10 minutos (300/hora) para reducir carga en API
Schedule::command('po:sync-credit-note-dynamics --all --limit=50')
  ->everyTenMinutes()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar y sincronizar documentos electrónicos de venta a Dynamics
// Procesa lotes de 200 documentos cada 2 minutos (6,000/hora) - más frecuente por mayor volumen
Schedule::command('electronic-document:verify-sync --all --limit=200')
  ->everyTwoMinutes()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Consultar estado de documentos electrónicos enviados a SUNAT
// Verificación de estado cada minuto (solo lectura, no crea jobs masivos)
Schedule::command('app:check-pending-electronic-documents')
  ->everyMinute()
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

