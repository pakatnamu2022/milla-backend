<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionsistema\SedeService;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Http\Request;

class SedeController extends Controller
{
  protected SedeService $service;

  public function __construct(SedeService $service)
  {
    $this->service = $service;
  }

  public function index()
  {
    return $this->service->list(request());
  }

  public function assignedSalesWorkers(Request $request)
  {
    return $this->service->getWorkers($request);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Sede $sede)
  {
    //
  }
}
