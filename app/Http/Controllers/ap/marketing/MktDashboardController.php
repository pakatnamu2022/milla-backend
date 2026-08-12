<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\marketing\MktDashboardService;
use Illuminate\Http\Request;

class MktDashboardController extends Controller
{
  protected MktDashboardService $service;

  public function __construct(MktDashboardService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    try {
      return $this->success($this->service->summary($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function monthly(Request $request)
  {
    try {
      return $this->success($this->service->monthly($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
