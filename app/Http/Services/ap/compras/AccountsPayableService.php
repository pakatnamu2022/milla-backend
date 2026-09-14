<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\AccountPayableCommentResource;
use App\Http\Resources\ap\compras\AccountPayableResource;
use App\Http\Services\BaseService;
use App\Models\ap\compras\AccountPayable;
use App\Models\ap\compras\AccountPayableComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AccountsPayableService extends BaseService
{
  public function list(Request $request)
  {
    $query = AccountPayable::query()
      ->where('monto_sin_aplicar', '>', 0)
      ->withCount('comments');

    $summaryBase = $this->applyFilters(
      AccountPayable::query()->where('monto_sin_aplicar', '>', 0),
      $request,
      AccountPayable::filters,
    );

    $agg = (clone $summaryBase)->selectRaw('
      COUNT(*) as total_documents,
      SUM(monto) as total_amount,
      SUM(monto_sin_aplicar) as total_pending
    ')->first();

    $extra = [
      'summary' => [
        'total_documents' => (int)($agg->total_documents ?? 0),
        'total_amount'    => $this->round($agg->total_amount),
        'total_pending'   => $this->round($agg->total_pending),
        'breakdown'       => $this->breakdownByMoneda(clone $summaryBase),
      ],
    ];

    return $this->getFilteredResults(
      $query,
      $request,
      AccountPayable::filters,
      AccountPayable::sorts,
      AccountPayableResource::class,
      [],
      $extra,
    );
  }

  public function show(int $id): AccountPayableResource
  {
    $record = AccountPayable::with(['comments.user'])->findOrFail($id);

    return new AccountPayableResource($record);
  }

  public static function dashboardCacheKey(string $company): string
  {
    return "accounts_payable:dashboard:{$company}";
  }

  public function dashboard(string $company = 'automotores', array $filters = []): array
  {
    $isFiltered = !empty(array_filter($filters));

    if ($isFiltered) {
      return $this->buildDashboard($company, $filters);
    }

    return Cache::rememberForever(self::dashboardCacheKey($company), fn() => $this->buildDashboard($company, []));
  }

  public function storeComment(int $id, array $data): AccountPayableCommentResource
  {
    $record = AccountPayable::findOrFail($id);

    $comment = AccountPayableComment::create([
      'accounts_payable_id' => $record->id,
      'user_id'             => auth()->id(),
      'comment'              => $data['comment'],
    ]);

    $comment->load('user');

    return new AccountPayableCommentResource($comment);
  }

  public function updateComment(int $commentId, array $data): AccountPayableCommentResource
  {
    $comment = AccountPayableComment::findOrFail($commentId);

    if (!$comment->created_at->isToday()) {
      throw new \Exception('Solo se pueden editar comentarios del día de hoy.');
    }

    $comment->update(['comment' => $data['comment']]);
    $comment->load('user');

    return new AccountPayableCommentResource($comment);
  }

  public function destroyComment(int $commentId): array
  {
    $comment = AccountPayableComment::findOrFail($commentId);

    if (!$comment->created_at->isToday()) {
      throw new \Exception('Solo se pueden eliminar comentarios del día de hoy.');
    }

    $comment->delete();

    return ['message' => 'Comentario eliminado correctamente.'];
  }

  // ─── Private helpers ────────────────────────────────────────────────────────

  private function breakdownByMoneda($query): array
  {
    $rows = $query
      ->selectRaw('moneda, COUNT(*) as total_documents, SUM(monto) as total_amount, SUM(monto_sin_aplicar) as total_pending')
      ->whereNotNull('moneda')
      ->groupBy('moneda')
      ->orderByDesc('total_pending')
      ->get();

    return $rows->map(fn($row) => [
      'label'            => trim((string)$row->moneda) ?: 'N/D',
      'total_documents'  => (int)($row->total_documents ?? 0),
      'total_amount'     => $this->round($row->total_amount),
      'total_pending'    => $this->round($row->total_pending),
    ])->values()->toArray();
  }

  private function buildDashboard(string $company, array $filters = []): array
  {
    $monedas = !empty($filters['monedas']) ? (array)$filters['monedas'] : null;

    $base = fn() => AccountPayable::where('company', $company)
      ->where('monto_sin_aplicar', '>', 0)
      ->when($monedas !== null, fn($q) => $q->whereIn('moneda', $monedas));

    $summary = $base()->selectRaw('
      COUNT(*) as total_documents,
      SUM(monto) as total_amount,
      SUM(monto_sin_aplicar) as total_pending
    ')->first();

    // By moneda
    $byMoneda = $base()
      ->selectRaw('moneda as label, SUM(monto_sin_aplicar) as value')
      ->whereNotNull('moneda')
      ->groupBy('moneda')
      ->orderByDesc('value')
      ->get();

    // By month (fecha_contable)
    $byMonth = $base()
      ->selectRaw("DATE_FORMAT(fecha_contable, '%Y-%m') as label, SUM(monto_sin_aplicar) as value")
      ->whereNotNull('fecha_contable')
      ->groupBy('label')
      ->orderBy('label')
      ->get();

    // Top 10 proveedores por saldo sin aplicar
    $topProveedores = $base()
      ->selectRaw('proveedor_nombre as label, SUM(monto_sin_aplicar) as value')
      ->whereNotNull('proveedor_nombre')
      ->groupBy('proveedor_nombre')
      ->orderByDesc('value')
      ->limit(10)
      ->get();

    $syncedAt = $base()->max('synced_at');

    return [
      'synced_at' => $syncedAt,
      'summary'   => [
        'total_documents' => (int)($summary->total_documents ?? 0),
        'total_amount'    => $this->round($summary->total_amount),
        'total_pending'   => $this->round($summary->total_pending),
      ],
      'charts'    => [
        [
          'id'       => 'pending_by_moneda',
          'title'    => 'Saldo por Moneda',
          'type'     => 'pie',
          'labels'   => $byMoneda->pluck('label')->map(fn($v) => trim((string)$v) ?: 'N/D')->values()->toArray(),
          'datasets' => [[
            'label' => 'Saldo sin aplicar',
            'data'  => $byMoneda->pluck('value')->map(fn($v) => $this->round($v))->values()->toArray(),
          ]],
        ],
        [
          'id'       => 'pending_by_month',
          'title'    => 'Saldo por Mes Contable',
          'type'     => 'line',
          'labels'   => $byMonth->pluck('label')->values()->toArray(),
          'datasets' => [[
            'label' => 'Saldo sin aplicar',
            'data'  => $byMonth->pluck('value')->map(fn($v) => $this->round($v))->values()->toArray(),
          ]],
        ],
        [
          'id'       => 'top_proveedores',
          'title'    => 'Top 10 Proveedores por Saldo',
          'type'     => 'bar',
          'labels'   => $topProveedores->pluck('label')->values()->toArray(),
          'datasets' => [[
            'label' => 'Saldo sin aplicar',
            'data'  => $topProveedores->pluck('value')->map(fn($v) => $this->round($v))->values()->toArray(),
          ]],
        ],
      ],
    ];
  }

  private function round(mixed $value): float
  {
    return round((float)($value ?? 0), 2);
  }
}
