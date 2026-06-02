<?php

namespace App\Http\Services\dp\comercial;

use App\Http\Resources\dp\comercial\AccountReceivableCommentResource;
use App\Http\Resources\dp\comercial\AccountReceivableResource;
use App\Http\Services\BaseService;
use App\Jobs\SyncAccountsReceivableJob;
use App\Models\dp\comercial\AccountReceivable;
use App\Models\dp\comercial\AccountReceivableComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AccountsReceivableService extends BaseService
{
  public function sync(string $company = 'deposito'): array
  {
    dispatch(new SyncAccountsReceivableJob($company));

    return ['message' => "Sync started for company '{$company}'."];
  }

  public function list(Request $request)
  {
    $query = AccountReceivable::query()
      ->with('sede')
      ->withCount('comments');

    return $this->getFilteredResults(
      $query,
      $request,
      AccountReceivable::filters,
      AccountReceivable::sorts,
      AccountReceivableResource::class,
    );
  }

  public function show(int $id): AccountReceivableResource
  {
    $record = AccountReceivable::with(['sede', 'comments.user', 'comments.sede'])->findOrFail($id);

    return new AccountReceivableResource($record);
  }

  public static function filterTreeCacheKey(string $company): string
  {
    return "accounts_receivable:filter_tree:{$company}";
  }

  public function filterTree(string $company = 'deposito'): array
  {
    return Cache::rememberForever(self::filterTreeCacheKey($company), function () use ($company) {
      $rows = AccountReceivable::query()
        ->select('sede_id', 'overdue_status', 'due_year')
        ->with('sede:id,suc_abrev,localidad')
        ->where('company', $company)
        ->whereNotNull('sede_id')
        ->whereNotNull('overdue_status')
        ->whereNotNull('due_year')
        ->distinct()
        ->orderBy('sede_id')
        ->orderBy('overdue_status')
        ->orderByDesc('due_year')
        ->get();

      $tree = [];

      foreach ($rows as $row) {
        $sedeId = $row->sede_id;

        if (!isset($tree[$sedeId])) {
          $tree[$sedeId] = [
            'sede_id'   => $sedeId,
            'sede_name' => $row->sede?->suc_abrev ?? $row->sede?->localidad ?? "Sede {$sedeId}",
            'statuses'  => [],
          ];
        }

        $status = $row->overdue_status;

        if (!isset($tree[$sedeId]['statuses'][$status])) {
          $tree[$sedeId]['statuses'][$status] = [
            'status' => $status,
            'years'  => [],
          ];
        }

        $tree[$sedeId]['statuses'][$status]['years'][] = $row->due_year;
      }

      return array_values(array_map(function ($sede) {
        $sede['statuses'] = array_values($sede['statuses']);
        return $sede;
      }, $tree));
    });
  }

  public function storeComment(int $id, array $data): AccountReceivableCommentResource
  {
    $record = AccountReceivable::findOrFail($id);

    $comment = AccountReceivableComment::create([
      'accounts_receivable_id' => $record->id,
      'sede_id'                => $record->sede_id,
      'user_id'                => auth()->id(),
      'comment'                => $data['comment'],
    ]);

    $comment->load(['user', 'sede']);

    return new AccountReceivableCommentResource($comment);
  }
}
