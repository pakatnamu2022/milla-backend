<?php

use App\Jobs\WarmAdoptionCacheJob;
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
// Ejecuta cada minuto con límite de 10 jobs pendientes máximo en cola
Schedule::command('po:verify-migration --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar y migrar guías de remisión de COMERCIAL (vehículos) pendientes
// Ejecuta cada 10 segundos con límite de 10 jobs pendientes máximo en cola
Schedule::command('shipping-guide:verify-migration --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar y migrar guías de remisión de POSVENTA (productos) pendientes
// Ejecuta cada 10 segundos con límite de 10 jobs pendientes máximo en cola
Schedule::command('shipping-guides-postventa:verify-migration --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar invoice_dynamics desde Dynamics
// Ejecuta cada minuto con límite de 10 jobs pendientes máximo en cola
Schedule::command('po:sync-invoice-dynamics --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar shipping_guide_dynamics desde Dynamics
// Ejecuta cada minuto con límite de 10 jobs pendientes máximo en cola
Schedule::command('shipping-guide:sync-dynamics --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar credit_note_dynamics desde Dynamics
// Ejecuta cada minuto con límite de 10 jobs pendientes máximo en cola
Schedule::command('po:sync-credit-note-dynamics --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar y sincronizar documentos electrónicos de venta a Dynamics
// Ejecuta cada minuto con límite de 10 jobs pendientes máximo en cola
Schedule::command('electronic-document:verify-sync --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Consultar estado de documentos electrónicos enviados a SUNAT
// Verificación de estado cada minuto (solo lectura, no crea jobs masivos)
Schedule::command('app:check-pending-electronic-documents')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Cerrar evaluaciones cuyo end_date ya venció
// Ejecuta diariamente a las 23:00 hora Lima
Schedule::command('evaluation:close-expired')
  ->dailyAt('23:00')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Redistribuir leads pendientes (use=0, >24h) entre asesores del mismo grupo shop+marca
// Ejecuta diariamente a medianoche (hora Lima)
//Schedule::command('ap:redistribute-potential-buyers')
//  ->dailyAt('00:00')
//  ->timezone('America/Lima')
//  ->withoutOverlapping()
//  ->runInBackground();

// Sincronizar ajustes de inventario desde Dynamics
// Ejecuta cada minuto para consultar y procesar ajustes de POSTVENTA de los últimos 6 meses
Schedule::command('inventory:sync-adjustments-dynamics')
  ->everyFiveMinutes()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Calentar cache del dashboard de adopción cada 10 minutos en horario laboral
Schedule::job(new WarmAdoptionCacheJob())
  ->everySixHours()
  ->between('7:00', '20:00')
  ->timezone('America/Lima')
  ->withoutOverlapping();

// Notificar a encargados de almacén sobre stock bajo
// Ejecuta diariamente a las 8:00 AM hora Lima
Schedule::command('warehouse:notify-low-stock')
  ->dailyAt('08:00')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sincronizar FAC_INVOICE desde RM20101_MILLA_DOCFV (DBTP2) — Módulo Transportes
Schedule::command('tp:sync-fac-invoice')
  ->dailyAt('08:00')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Enviar reportes de CxC por vencer (≤2 días) — diariamente a las 8am
Schedule::command('ar:send-due-reports')
  ->dailyAt('08:00')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Verificar asientos contables procesados por GP y completar entregas de vehículos
Schedule::command('accounting-entry:verify --all')
  ->everyTenSeconds()
  ->between('6:00', '23:59')
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

// Sync attendance punches from ZKBioTime — 4 times daily
foreach (['10:00', '15:00', '17:00', '22:00'] as $time) {
  Schedule::command('sync:attendance')
    ->dailyAt($time)
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->runInBackground();
}

// Reporte de ausencias — lunes a sábado a las 9:30 a.m.
Schedule::command('attendance:send-absent-report')
  ->dailyAt('09:30')
  ->days([1, 2, 3, 4, 5, 6])
  ->timezone('America/Lima')
  ->withoutOverlapping()
  ->runInBackground();

