<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexAreaRequest;
use App\Http\Services\gp\gestionsistema\AreaService;

class AreaController extends Controller
{
  protected AreaService $service;

  public function __construct()
  {
    $this->service = new AreaService();
  }

  public function index(IndexAreaRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

}
