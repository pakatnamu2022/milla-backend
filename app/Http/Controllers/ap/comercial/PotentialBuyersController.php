<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexPotentialBuyersRequest;
use App\Http\Requests\ap\comercial\StorePotentialBuyersRequest;
use App\Http\Requests\ap\comercial\UpdatePotentialBuyersRequest;
use App\Http\Services\ap\comercial\PotentialBuyersService;
use App\Models\ap\comercial\PotentialBuyers;

class PotentialBuyersController extends Controller
{
  protected PotentialBuyersService $service;

  public function __construct(PotentialBuyersService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPotentialBuyersRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StorePotentialBuyersRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
