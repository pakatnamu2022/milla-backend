<?php

namespace App\Http\Controllers\dp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\dp\comercial\StoreAccountReceivableCommentRequest;
use App\Http\Requests\dp\comercial\UpdateAccountReceivableCommentRequest;
use App\Http\Services\dp\comercial\AccountsReceivableService;
use App\Jobs\SendDueAccountsReceivableReportsJob;
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

  public function dashboard(Request $request)
  {
    try {
      $company = $request->input('company', 'deposito');
      return $this->success($this->service->dashboard($company));
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

  public function updateComment(UpdateAccountReceivableCommentRequest $request, $commentId)
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

  public function sendReports(Request $request)
  {
    try {
      $company = $request->input('company', 'deposito');
      return $this->success($this->service->sendSedeReports($company));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function sendDueReports(Request $request)
  {
    try {
      $company = $request->input('company', 'deposito');
      SendDueAccountsReceivableReportsJob::dispatch($company);
      return $this->success(['message' => "Reportes enviados a los correos"]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function sendGlobalExcel(Request $request)
  {
    try {
      $company = $request->input('company', 'deposito');
      return $this->success($this->service->sendGlobalExcel($company));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function downloadExcel(Request $request)
  {
    try {
      $company = $request->input('company', 'deposito');
      ['content' => $content, 'filename' => $filename] = $this->service->downloadGlobalExcel($company);

      return response($content, 200, [
        'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

}
