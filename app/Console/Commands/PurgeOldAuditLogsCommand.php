<?php

namespace App\Console\Commands;

use App\Models\AuditLogs;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeOldAuditLogsCommand extends Command
{
  protected $signature = 'audit-logs:purge
                          {--months=3 : Número de meses hacia atrás a conservar}
                          {--chunk=5000 : Tamaño del lote de borrado}
                          {--dry-run : Mostrar cuántos registros se purgarían sin borrar}
                          {--optimize : Compactar la tabla con OPTIMIZE TABLE después de purgar}';

  protected $description = 'Purga registros antiguos de la tabla audit_logs y elimina todos los registros con método GET';

  public function handle(): int
  {
    $months = (int) $this->option('months');
    $chunkSize = (int) $this->option('chunk');
    $dryRun = $this->option('dry-run');

    // Validaciones
    if ($months < 1) {
      $this->error('El número de meses debe ser al menos 1');
      return 1;
    }

    if ($chunkSize < 100 || $chunkSize > 50000) {
      $this->error('El tamaño del lote debe estar entre 100 y 50000');
      return 1;
    }

    $startTime = microtime(true);
    $cutoffDate = Carbon::now('America/Lima')->subMonths($months);

    $this->info("🗑️  Iniciando purga de audit_logs");
    $this->line("   Fecha de corte: " . $cutoffDate->format('Y-m-d H:i:s'));
    $this->line("   Conservar: últimos $months meses (excepto GET)");
    $this->line("   Tamaño de lote: $chunkSize");
    $this->line("   Nota: Se eliminarán TODOS los registros GET sin importar su fecha");
    $this->line('');

    // Contar registros candidatos a purga
    $totalOldRecords = AuditLogs::where('created_at', '<', $cutoffDate)->count();
    $totalGetRecords = AuditLogs::where('method', 'GET')->count();

    // Total combinado (evitando duplicados)
    $totalToPurge = AuditLogs::where(function ($query) use ($cutoffDate) {
      $query->where('created_at', '<', $cutoffDate)
        ->orWhere('method', 'GET');
    })->count();

    $this->info("📊 Registros antiguos (>" . $months . " meses): " . number_format($totalOldRecords));
    $this->info("📊 Registros con método GET (todas las fechas): " . number_format($totalGetRecords));
    $this->info("📊 Total candidatos a purga: " . number_format($totalToPurge));

    if ($totalToPurge === 0) {
      $this->info('✅ No hay registros para purgar.');
      return 0;
    }

    // Si es dry-run, terminar aquí
    if ($dryRun) {
      $this->line('');
      $this->warn('⚠️  MODO DRY-RUN: No se realizaron cambios.');
      $this->info("   Se purgarían " . number_format($totalToPurge) . " registros.");
      return 0;
    }

    // Confirmación antes de proceder
    $this->line('');
    $this->warn('⚠️  Se procederá a ELIMINAR permanentemente los registros.');

    // Proceso de borrado en lotes
    $this->line('');
    $this->info('🔄 Iniciando borrado en lotes...');

    $totalDeleted = 0;
    $iteration = 0;

    while (true) {
      $iteration++;

      // Borrar un lote (registros antiguos O método GET)
      $deleted = AuditLogs::where(function ($q) use ($cutoffDate) {
        $q->where('created_at', '<', $cutoffDate)
          ->orWhere('method', 'GET');
      })
        ->limit($chunkSize)
        ->delete();

      if ($deleted === 0) {
        break;
      }

      $totalDeleted += $deleted;
      $progress = min(100, round(($totalDeleted / $totalToPurge) * 100, 1));

      $this->line(sprintf(
        "   Lote #%d: %s eliminados | Total: %s / %s (%s%%)",
        $iteration,
        number_format($deleted),
        number_format($totalDeleted),
        number_format($totalToPurge),
        $progress
      ));

      // Pausa entre lotes para reducir carga I/O
      usleep(200000); // 200ms
    }

    // Compactar tabla si se solicitó --optimize
    $optimized = false;
    if ($this->option('optimize')) {
      $this->line('');
      $this->info('🔧 Compactando tabla audit_logs...');

      DB::statement('OPTIMIZE TABLE audit_logs');

      $optimized = true;
      $this->info('✅ Tabla compactada.');
    }

    $duration = round(microtime(true) - $startTime, 2);

    $this->line('');
    $this->info("✅ Purga completada exitosamente");
    $this->line("   Registros eliminados: " . number_format($totalDeleted));
    $this->line("   Lotes procesados: $iteration");
    $this->line("   Duración: " . $duration . "s");

    // Logging del resultado
    Log::info('audit_logs:purge ejecutado', [
      'cutoff_date' => $cutoffDate->toDateTimeString(),
      'months_retained' => $months,
      'chunk_size' => $chunkSize,
      'total_old_records' => $totalOldRecords,
      'total_get_records' => $totalGetRecords,
      'total_deleted' => $totalDeleted,
      'iterations' => $iteration,
      'duration_seconds' => $duration,
      'optimized' => $optimized,
    ]);

    return 0;
  }
}