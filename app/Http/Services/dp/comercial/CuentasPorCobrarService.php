<?php

namespace App\Http\Services\dp\comercial;

use App\Http\Resources\dp\comercial\CuentaPorCobrarComentarioResource;
use App\Http\Resources\dp\comercial\CuentaPorCobrarResource;
use App\Http\Services\BaseService;
use App\Jobs\SyncCuentasPorCobrarJob;
use App\Models\dp\comercial\CuentaPorCobrar;
use App\Models\dp\comercial\CuentaPorCobrarComentario;
use Illuminate\Http\Request;

class CuentasPorCobrarService extends BaseService
{
  public function sync(string $company = 'deposito'): array
  {
    dispatch(new SyncCuentasPorCobrarJob($company));

    return ['message' => "Sync started for company '{$company}'."];
  }

  public function list(Request $request)
  {
    $query = CuentaPorCobrar::query()
      ->with('sede')
      ->withCount('comments');

    return $this->getFilteredResults(
      $query,
      $request,
      CuentaPorCobrar::filters,
      CuentaPorCobrar::sorts,
      CuentaPorCobrarResource::class,
    );
  }

  public function show(int $id): CuentaPorCobrarResource
  {
    $record = CuentaPorCobrar::with(['sede', 'comments.user', 'comments.sede'])->findOrFail($id);

    return new CuentaPorCobrarResource($record);
  }

  public function storeComment(int $id, array $data): CuentaPorCobrarComentarioResource
  {
    $record = CuentaPorCobrar::findOrFail($id);

    $comment = CuentaPorCobrarComentario::create([
      'accounts_receivable_id' => $record->id,
      'sede_id'                => $record->sede_id,
      'user_id'                => auth()->id(),
      'comment'                => $data['comment'],
    ]);

    $comment->load(['user', 'sede']);

    return new CuentaPorCobrarComentarioResource($comment);
  }
}
