<?php

namespace App\Jobs;

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
      $records = DB::table('fac_invoice')
        ->whereNotNull('serie')
        ->whereNotNull('numero')
        ->where('status_envio_gp', 1)
        ->where('status_deleted', 1)
        ->where('status_envio_sunat', 1)
        ->whereYear('fecha_emision', 2026)
        ->get(['serie', 'numero', 'fecha_vencimiento']);

      foreach ($records->chunk(500) as $chunk) {
        $rows = $chunk->map(function ($record) {
          $documentoId = 'FA ' . trim($record->serie) . '-' . str_pad(trim($record->numero), 8, '0', STR_PAD_LEFT);

          return [
            'DocumentoId' => $documentoId,
            'FechaVencimiento' => $this->parseDate($record->fecha_vencimiento),
          ];
        })->filter(fn($row) => $row['DocumentoId'] !== 'FA-00000000')->values()->toArray();

        if (empty($rows)) {
          continue;
        }

        DB::connection('dbtp2')
          ->table('RM20101_MILLA_DOCFV')
          ->upsert(
            $rows,
            ['DocumentoId'],
            ['FechaVencimiento']
          );

        $processed += count($rows);
      }

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
