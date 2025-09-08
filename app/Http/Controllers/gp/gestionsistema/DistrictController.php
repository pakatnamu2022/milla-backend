<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexDistrictRequest;
use App\Http\Services\gp\gestionsistema\DistrictService;

class DistrictController extends Controller
{
  protected DistrictService $service;

  public function __construct(DistrictService $service)
  {
    $this->service = $service;
  }

  public function index(IndexDistrictRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
