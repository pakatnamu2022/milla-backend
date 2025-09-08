<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexProvinceRequest;
use App\Http\Services\gp\gestionsistema\ProvinceService;

class ProvinceController extends Controller
{
  protected ProvinceService $service;

  public function __construct(ProvinceService $service)
  {
    $this->service = $service;
  }

  public function index(IndexProvinceRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
