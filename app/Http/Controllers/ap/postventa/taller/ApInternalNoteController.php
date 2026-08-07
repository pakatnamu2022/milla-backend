<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\facturacion\IndexApInternalNoteRequest;
use App\Http\Services\ap\postventa\taller\ApInternalNoteService;

class ApInternalNoteController extends Controller
{
  protected ApInternalNoteService $service;

  public function __construct(ApInternalNoteService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApInternalNoteRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function verifyInternalNoteMigration($id)
  {
    try {
      return $this->service->verifyInternalNoteMigration($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updateInternalNoteAccountingStatus($id)
  {
    try {
      return $this->service->updateInternalNoteAccountingStatus($id);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
