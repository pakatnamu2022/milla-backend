<?php

namespace App\Jobs;

use App\Models\dp\comercial\AccountReceivable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncAccountsReceivableCollectionsJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  private const COMPANY_CONNECTION_MAP = [
    'deposito'    => 'dbdp2',
    'automotores' => 'dbtp3',
  ];

  public function __construct(
    public string  $company        = 'deposito',
    public ?string $documentNumber = null
  ) {
    $this->onQueue('receivable-accounts');
  }

  public function handle(): int
  {
    if ($this->company !== 'automotores') {
      return 0;
    }

    $connection = self::COMPANY_CONNECTION_MAP[$this->company]
      ?? throw new \Exception("Company '{$this->company}' has no configured connection.");

    $pdo = DB::connection($connection)->getPdo();

    $query = AccountReceivable::where('company', $this->company)
      ->where('overdue_status', 'PAGADO');

    if ($this->documentNumber) {
      $query->where('document_number', trim($this->documentNumber));
    } else {
      $query->whereNull('collection_reference');
    }

    $updated = 0;
    $query->get()->each(function (AccountReceivable $record) use ($pdo, &$updated) {
      $stmt = $pdo->prepare("EXEC Reporte_Venta_Cancelaciones_Detalle_SIAN ?");
      $stmt->execute([trim($record->document_number)]);

      $rows = collect();
      do {
        if ($stmt->columnCount() > 0) {
          $rows = collect($stmt->fetchAll(\PDO::FETCH_OBJ));
          break;
        }
      } while ($stmt->nextRowset());

      if ($rows->isEmpty()) {
        return;
      }

      $record->update(['collection_reference' => $this->formatCollections($rows)]);
      $updated++;
    });

    return $updated;
  }

  private function formatCollections($collections): string
  {
    return $collections->map(function ($c) {
      $cobro    = trim($c->Cobro ?? '');
      $date     = \Carbon\Carbon::parse($c->CobroFecha)->format('d/m/Y');
      $symbol   = trim($c->CobroMonedaOrigenSimbolo ?? '$');
      $amount   = number_format((float)($c->CobroMonto ?? 0), 2);
      $currency = trim($c->CobroMoneda ?? 'USD');
      $type     = trim($c->TipoTransaccion ?? '');
      $account  = trim($c->NroCuenta ?? '');

      return "{$cobro} | {$date} | {$symbol}{$amount} {$currency} | {$type} {$account}";
    })->implode("\n");
  }

  public function failed(\Throwable $exception): void
  {
    //
  }
}
