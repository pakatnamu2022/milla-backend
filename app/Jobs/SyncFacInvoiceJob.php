<?php

namespace App\Jobs;

use App\Models\tp\comercial\FacInvoice;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncFacInvoiceJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  public function __construct()
  {
    $this->onQueue('fac_invoice_sync');
  }

  public function handle(): void
  {
    $syncedAt = now();
    $processed = 0;

    try {
      FacInvoice::with('tipoComprobante')
        ->whereNotNull('serie')
        ->whereNotNull('numero')
        ->where('status_envio_gp', 1)
        ->where('status_deleted', 1)
        ->where('status_envio_sunat', 1)
        ->select(['id', 'serie', 'numero', 'fecha_vencimiento', 'tipo_comprobante_id'])
        ->chunk(500, function ($chunk) use (&$processed) {
          $rows = $chunk->map(fn($record) => [
            'DocumentoId' => $record->documento_id,
            'FechaVencimiento' => $this->parseDate($record->fecha_vencimiento),
          ])->filter(fn($row) => !str_ends_with($row['DocumentoId'], '-00000000'))->values()->toArray();

          if (empty($rows)) {
            return;
          }

          DB::connection('dbtp2')
            ->table('RM20101_MILLA_DOCFV')
            ->upsert(
              $rows,
              ['DocumentoId'],
              ['FechaVencimiento']
            );

          $processed += count($rows);
        });

      Log::info('SyncFacInvoiceJob completado', [
        'total_procesados' => $processed,
        'synced_at' => $syncedAt,
      ]);
    } catch (Throwable $e) {
      Log::error('SyncFacInvoiceJob error', [
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  private function parseDate(mixed $value): ?string
  {
    if (empty($value)) {
      return null;
    }

    try {
      $date = Carbon::parse($value);
      if ($date->year <= 1900) {
        return null;
      }
      return $date->toDateString();
    } catch (Throwable) {
      return null;
    }
  }

  public function failed(Throwable $exception): void
  {
    Log::error('SyncFacInvoiceJob falló definitivamente', [
      'error' => $exception->getMessage(),
    ]);
  }
}
