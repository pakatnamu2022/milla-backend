<?php

namespace App\Http\Services\dp\comercial;

use App\Http\Resources\dp\comercial\AccountReceivableCommentResource;
use App\Http\Resources\dp\comercial\AccountReceivableResource;
use App\Http\Services\BaseService;
use App\Jobs\SyncAccountsReceivableJob;
use App\Models\dp\comercial\AccountReceivable;
use App\Models\dp\comercial\AccountReceivableComment;
use Illuminate\Http\Request;

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
