<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Jobs\SyncFacInvoiceJob;
use Illuminate\Http\JsonResponse;

class FacInvoiceController extends Controller
{
  public function sync(): JsonResponse
  {
    SyncFacInvoiceJob::dispatch();

    return response()->json(['message' => 'Sincronización iniciada correctamente'], 200);
  }
}
