<?php

namespace App\Http\Controllers\dp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\dp\comercial\StoreCuentaPorCobrarComentarioRequest;
use App\Http\Services\dp\comercial\CuentasPorCobrarService;
use Illuminate\Http\Request;
use Throwable;

class CuentasPorCobrarController extends Controller
{
  protected CuentasPorCobrarService $service;

  public function __construct(CuentasPorCobrarService $service)
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

  public function storeComment(StoreCuentaPorCobrarComentarioRequest $request, $id)
  {
    try {
      return $this->success($this->service->storeComment($id, $request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
