<?php

namespace App\Console\Commands;

use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncDocFVCommand extends Command
{
  protected $signature = 'electronic-document:sync-docfv
                          {--limit=0 : Número máximo de documentos a procesar (0 = todos)}
                          {--dry-run : Solo muestra cuántos faltarían sin insertar}';

  protected $description = 'Backfill de RM20101_DOCFV en DBTP3 para documentos AP completados en Dynamics';

  public function handle(): int
  {
    $limit = (int) $this->option('limit');
    $dryRun = $this->option('dry-run');

    $this->info('Obteniendo documentos completados en Dynamics...');

    // Obtener IDs ya presentes en RM20101_DOCFV
    $existingIds = DB::connection(Company::CONNECTION_DYNAMICS_3)
      ->table('RM20101_DOCFV')
      ->pluck('DocumentoId')
      ->flip();

    $inserted = 0;
    $skipped = 0;

    $query = ElectronicDocument::where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->whereNotNull('fecha_de_vencimiento')
      ->whereNull('deleted_at')
      ->select(['id', 'full_number', 'fecha_de_vencimiento'])
      ->orderBy('id', 'desc');

    if ($limit > 0) {
      $query->limit($limit);
    }

    $query->chunk(100, function ($documents) use ($existingIds, $dryRun, &$inserted, &$skipped) {
        $rows = [];

        foreach ($documents as $document) {
          $documentoId = $document->full_number;

          if (isset($existingIds[$documentoId])) {
            $skipped++;
            continue;
          }

          $rows[] = [
            'DocumentoId'      => $documentoId,
            'FechaVencimiento' => $document->fecha_de_vencimiento,
          ];
        }

        if (empty($rows)) {
          return;
        }

        if ($dryRun) {
          $inserted += count($rows);
          return;
        }

        try {
          DB::connection(Company::CONNECTION_DYNAMICS_3)
            ->table('RM20101_DOCFV')
            ->upsert($rows, ['DocumentoId'], ['FechaVencimiento']);

          $inserted += count($rows);
        } catch (\Exception $e) {
          Log::error('SyncDocFVCommand error al insertar chunk', ['error' => $e->getMessage()]);
          $this->error('Error en chunk: ' . $e->getMessage());
        }
      });

    $action = $dryRun ? 'insertarían' : 'insertados';
    $this->info("Completado — {$action}: {$inserted} | ya existían: {$skipped}");

    return Command::SUCCESS;
  }
}
