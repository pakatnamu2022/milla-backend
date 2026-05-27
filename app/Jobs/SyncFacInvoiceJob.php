<?php

namespace App\Jobs;

use App\Models\tp\facturacion\FacInvoice;
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
      $records = DB::connection('dbtp2')
        ->table('RM20101_MILLA_DOCFV')
        ->get();

      $chunks = $records->chunk(500);

      foreach ($chunks as $chunk) {
        $rows = $chunk->map(function ($record) {
          return [
            'DocumentoId'       => trim($record->DOCNUMBR ?? ''),
            'FechaVencimiento'  => $this->parseDate($record->DUEDATE ?? null),
          ];
        })->filter(fn($row) => $row['DocumentoId'] !== '')->values()->toArray();

        if (empty($rows)) {
          continue;
        }

        FacInvoice::upsert(
          $rows,
          ['DocumentoId'],
          ['FechaVencimiento']
        );

        $processed += count($rows);
      }

      Log::info('SyncFacInvoiceJob completado', [
        'total_procesados' => $processed,
        'synced_at'        => $syncedAt,
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
