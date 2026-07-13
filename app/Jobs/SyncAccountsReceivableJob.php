<?php

namespace App\Jobs;

use App\Http\Services\dp\comercial\AccountsReceivableService;
use App\Models\dp\comercial\AccountReceivable;
use App\Models\dp\comercial\AccountReceivableComment;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SyncAccountsReceivableJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  private const COMPANY_CONNECTION_MAP = [
    'deposito'    => 'dbdp2',
    'automotores' => 'dbtp3',
  ];

  public function __construct(
    public string $company = 'deposito'
  )
  {
    $this->onQueue('receivable-accounts');
  }

  public function handle(): void
  {
    $connection = self::COMPANY_CONNECTION_MAP[$this->company]
      ?? throw new \Exception("Company '{$this->company}' has no configured connection.");

    $pdo  = DB::connection($connection)->getPdo();
    $stmt = $pdo->prepare("EXEC GP_Reporte_Indicador_Comercial_CuentasPorCobrar '%'");
    $stmt->execute();

    $rows = [];
    do {
      if ($stmt->columnCount() > 0) {
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        break;
      }
    } while ($stmt->nextRowset());

    $sedeMap = $this->buildSedeMap();
    $batchAt = now()->toDateTimeString();
    $company = $this->company;

    $spDocNumbers = collect($rows)
      ->map(fn($r) => trim((string)($r->DocumentoNumero ?? '')))
      ->filter()
      ->values()
      ->all();

    // Detect reversals: documents locally marked PAGADO that reappear in SP
    AccountReceivable::where('company', $company)
      ->where('overdue_status', 'PAGADO')
      ->whereIn('document_number', $spDocNumbers)
      ->get()
      ->each(function (AccountReceivable $record) {
        AccountReceivableComment::create([
          'accounts_receivable_id' => $record->id,
          'sede_id'                => $record->sede_id,
          'user_id'                => null,
          'comment'                => 'Documento reingresado al SP: se detectó anulación de cobro. Verificar movimiento.',
        ]);
      });

    collect($rows)
      ->chunk(100)
      ->each(function ($chunk) use ($sedeMap, $batchAt, $company) {
        $records = $chunk->map(fn($row) => $this->mapRow((array)$row, $sedeMap, $batchAt))->toArray();

        AccountReceivable::upsert(
          $records,
          ['company', 'document_number'],
          [
            'sede_id',
            'seller',
            'cashier',
            'client_id',
            'client_name',
            'client_id_real',
            'client_name_real',
            'document_date',
            'document_due_date',
            'due_year',
            'due_month',
            'overdue_days',
            'overdue_status',
            'currency',
            'exchange_rate',
            'amount',
            'balance',
            'amount_pen',
            'balance_pen',
            'branch',
            'observations',
            'collection_date',
            'synced_at',
            'updated_at',
          ]
        );
      });

    // Mark records absent from SP as PAGADO
    AccountReceivable::where('company', $company)
      ->where('synced_at', '<', $batchAt)
      ->where('overdue_status', '!=', 'PAGADO')
      ->update(['overdue_status' => 'PAGADO', 'updated_at' => $batchAt]);

    if ($company === 'automotores') {
      DB::statement("
        UPDATE accounts_receivable ar
        INNER JOIN ap_billing_electronic_documents ed
          ON ar.document_number = ed.full_number AND ed.deleted_at IS NULL
        SET ar.electronic_document_id = ed.id,
            ar.area_id = ed.area_id
        WHERE ar.company = 'automotores'
      ");
    }

    Cache::forget(AccountsReceivableService::filterTreeCacheKey($company));
    Cache::forget(AccountsReceivableService::dashboardCacheKey($company));
  }

  private function mapRow(array $row, array $sedeMap, string $now): array
  {
    $branch   = trim($row['Sucursal'] ?? '');
    $currency = strtoupper(trim($row['Moneda'] ?? 'PEN'));
    $amount   = (float)($row['DocumentoImporte'] ?? 0);
    $balance  = (float)($row['DocumentoDeuda'] ?? 0);
    $rate     = (float)($row['DocumentoTasaCambio'] ?? 1) ?: 1;

    $amountPen = $currency === 'PEN' ? $amount : $amount * $rate;
    $balancePen = $currency === 'PEN' ? $balance : $balance * $rate;

    return [
      'company'          => $this->company,
      'sede_id'          => $this->resolveSede($branch, $sedeMap),
      'seller'           => $row['Vendedor'] ?? null,
      'cashier'          => $row['Caja'] ?? null,
      'document_number'  => trim($row['DocumentoNumero'] ?? ''),
      'client_id'        => $row['ClienteId'] ?? null,
      'client_name'      => $row['ClienteNombre'] ?? null,
      'client_id_real'   => $row['ClienteIdReal'] ?: null,
      'client_name_real' => $row['ClienteNombreReal'] ?: null,
      'document_date'    => $this->parseDate($row['DocumentoFecha'] ?? null),
      'document_due_date' => $this->parseDate($row['DocumentoVencimiento'] ?? null),
      'due_year'         => $row['Anho_Vencimiento'] ?? null,
      'due_month'        => $row['Mes_Vencimiento'] ?? null,
      'overdue_days'     => $row['DiasVencidos'] ?? null,
      'overdue_status'   => $row['Estado_Vencido'] ?? null,
      'currency'         => $row['Moneda'] ?? null,
      'exchange_rate'    => $row['DocumentoTasaCambio'] ?? null,
      'amount'           => $amount,
      'balance'          => $balance,
      'amount_pen'       => $amountPen,
      'balance_pen'      => $balancePen,
      'branch'           => $branch ?: null,
      'observations'     => $row['Observaciones'] ?: null,
      'collection_date'  => $this->parseDate(trim($row['CobroFecha'] ?? '')),
      'synced_at'        => $now,
      'created_at'       => $now,
      'updated_at'       => $now,
    ];
  }

  private function buildSedeMap(): array
  {
    return Sede::select('id', 'localidad', 'suc_abrev', 'abreviatura')
      ->get()
      ->mapWithKeys(fn($s) => [strtoupper(trim($s->suc_abrev ?? $s->abreviatura ?? $s->localidad ?? '')) => $s->id])
      ->toArray();
  }

  private function resolveSede(string $branch, array $sedeMap): ?int
  {
    if (empty($branch)) {
      return null;
    }
    $key = strtoupper($branch);
    foreach ($sedeMap as $sedeKey => $sedeId) {
      if ($sedeKey && (str_contains($key, $sedeKey) || str_contains($sedeKey, $key))) {
        return $sedeId;
      }
    }
    return null;
  }

  private function parseDate(?string $value): ?string
  {
    if (empty($value) || trim($value) === '') {
      return null;
    }
    try {
      return \Carbon\Carbon::parse(trim($value))->toDateString();
    } catch (\Throwable) {
      return null;
    }
  }

  public function failed(\Throwable $exception): void
  {
    //
  }
}
