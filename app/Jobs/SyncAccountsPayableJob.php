<?php

namespace App\Jobs;

use App\Http\Services\ap\compras\AccountsPayableService;
use App\Models\ap\compras\AccountPayable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SyncAccountsPayableJob implements ShouldQueue
{
  use Queueable;

  public int $tries   = 3;
  public int $timeout = 300;

  private const COMPANY_CONNECTION_MAP = [
    'deposito'    => 'dbdp2',
    'automotores' => 'dbtp3',
  ];

  public function __construct(
    public string $company = 'automotores'
  )
  {
    $this->onQueue('payable-accounts');
  }

  public function handle(): void
  {
    $connection = self::COMPANY_CONNECTION_MAP[$this->company]
      ?? throw new \Exception("Company '{$this->company}' has no configured connection.");

    $pdo  = DB::connection($connection)->getPdo();
    $stmt = $pdo->prepare('EXEC SP_GP_ReporteDocumentosNoAplicadosCuentaPorPagar');
    $stmt->execute();

    $rows = [];
    do {
      if ($stmt->columnCount() > 0) {
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        break;
      }
    } while ($stmt->nextRowset());

    $batchAt      = now()->toDateTimeString();
    $company      = $this->company;
    $spDocumentos = [];

    collect($rows)
      ->chunk(100)
      ->each(function ($chunk) use ($company, $batchAt, &$spDocumentos) {
        $records = [];

        foreach ($chunk as $row) {
          $documento = trim((string)($row->Documento ?? ''));
          if ($documento === '') {
            continue;
          }

          $spDocumentos[] = $documento;

          $records[] = [
            'company'             => $company,
            'documento'           => $documento,
            'proveedor_documento' => trim((string)($row->ProveedorDocumento ?? '')) ?: null,
            'proveedor_nombre'    => trim((string)($row->ProveedorNombre ?? '')) ?: null,
            'fecha_documento'     => $this->parseDate($row->FechaDocumento ?? null),
            'fecha_contable'      => $this->parseDate($row->FechaContable ?? null),
            'moneda'              => trim((string)($row->Moneda ?? '')) ?: null,
            'monto'               => (float)($row->Monto ?? 0),
            'monto_sin_aplicar'   => (float)($row->MontoSinAplicar ?? 0),
            'synced_at'           => $batchAt,
            'created_at'          => $batchAt,
            'updated_at'          => $batchAt,
          ];
        }

        if (!empty($records)) {
          AccountPayable::upsert(
            $records,
            ['company', 'documento'],
            ['proveedor_documento', 'proveedor_nombre', 'fecha_documento', 'fecha_contable', 'moneda', 'monto', 'monto_sin_aplicar', 'synced_at', 'updated_at']
          );
        }
      });

    // Documentos que desaparecieron del SP → ya fueron pagados, monto_sin_aplicar = 0
    AccountPayable::where('company', $company)
      ->whereNotIn('documento', $spDocumentos)
      ->where('monto_sin_aplicar', '>', 0)
      ->update([
        'monto_sin_aplicar' => 0,
        'synced_at'         => $batchAt,
        'updated_at'        => $batchAt,
      ]);

    Cache::forget(AccountsPayableService::dashboardCacheKey($company));
  }

  private function parseDate(mixed $value): ?string
  {
    if (empty($value) || trim((string)$value) === '') {
      return null;
    }
    try {
      return \Carbon\Carbon::parse(trim((string)$value))->toDateString();
    } catch (\Throwable) {
      return null;
    }
  }

  public function failed(\Throwable $exception): void
  {
    //
  }
}
