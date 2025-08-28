<?php

namespace App\Http\Controllers\TypeCurrency;

use App\Http\Controllers\Controller;
use App\Http\Requests\TypeCurrency\IndexTypeCurrencyRequest;
use App\Http\Requests\TypeCurrency\StoreTypeCurrencyRequest;
use App\Http\Requests\TypeCurrency\UpdateTypeCurrencyRequest;
use App\Http\Services\TypeCurrency\TypeCurrencyService;
use App\Models\TypeCurrency;
use Illuminate\Http\Request;

class TypeCurrencyController extends Controller
{
  protected TypeCurrencyService $service;

  public function __construct(TypeCurrencyService $service)
  {
    $this->service = $service;
  }

  public function index(IndexTypeCurrencyRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreTypeCurrencyRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
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

  public function update(UpdateTypeCurrencyRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
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
