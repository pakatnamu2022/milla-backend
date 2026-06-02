<?php

namespace App\Http\Controllers\dp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\dp\comercial\StoreAccountReceivableCommentRequest;
use App\Http\Services\dp\comercial\AccountsReceivableService;
use Illuminate\Http\Request;
use Throwable;

class AccountsReceivableController extends Controller
{
  protected AccountsReceivableService $service;

  public function __construct(AccountsReceivableService $service)
  {
    $this->service = $service;
  }

  public function sync(Request $request)
  {
    try {
      $company = $request->input('company', 'deposito');
      return $this->success($this->service->sync($company));
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

  public function filterTree(Request $request)
  {
    try {
      $company = $request->input('company', 'deposito');
      return $this->success($this->service->filterTree($company));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function storeComment(StoreAccountReceivableCommentRequest $request, $id)
  {
    try {
      return $this->success($this->service->storeComment($id, $request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
