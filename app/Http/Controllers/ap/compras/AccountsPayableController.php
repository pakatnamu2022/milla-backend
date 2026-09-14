<?php

namespace App\Http\Controllers\ap\compras;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\compras\StoreAccountPayableCommentRequest;
use App\Http\Requests\ap\compras\UpdateAccountPayableCommentRequest;
use App\Http\Services\ap\compras\AccountsPayableService;
use App\Jobs\SyncAccountsPayableJob;
use App\Models\ap\compras\AccountPayable;
use Illuminate\Http\Request;
use Throwable;

class AccountsPayableController extends Controller
{
  protected AccountsPayableService $service;

  public function __construct(AccountsPayableService $service)
  {
    $this->service = $service;
  }

  public function sync(Request $request)
  {
    try {
      $company = $request->input('company', 'automotores');
      SyncAccountsPayableJob::dispatchSync($company);
      $total = AccountPayable::where('company', $company)->count();
      return $this->success([
        'message' => 'Sincronización completada',
        'synced'  => $total,
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function index(Request $request)
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function dashboard(Request $request)
  {
    try {
      $company = $request->input('company', 'automotores');
      $filters = [
        'monedas' => $request->input('moneda'),
      ];
      return $this->success($this->service->dashboard($company, $filters));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function storeComment(StoreAccountPayableCommentRequest $request, $id)
  {
    try {
      return $this->success($this->service->storeComment($id, $request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updateComment(UpdateAccountPayableCommentRequest $request, $commentId)
  {
    try {
      return $this->success($this->service->updateComment($commentId, $request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroyComment($commentId)
  {
    try {
      return $this->success($this->service->destroyComment($commentId));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
