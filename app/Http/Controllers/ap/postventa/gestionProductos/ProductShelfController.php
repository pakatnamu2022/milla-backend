<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\gestionProductos\AssignProductToShelfRequest;
use App\Http\Requests\ap\postventa\gestionProductos\IndexProductShelfRequest;
use App\Http\Requests\ap\postventa\gestionProductos\RemoveProductFromShelfRequest;
use App\Http\Requests\ap\postventa\gestionProductos\StoreProductShelfRequest;
use App\Http\Requests\ap\postventa\gestionProductos\UpdateProductShelfRequest;
use App\Http\Services\ap\postventa\gestionProductos\ProductShelfService;

class ProductShelfController extends Controller
{
  protected ProductShelfService $service;

  public function __construct(ProductShelfService $service)
  {
    $this->service = $service;
  }

  public function index(IndexProductShelfRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreProductShelfRequest $request)
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

  public function update(UpdateProductShelfRequest $request, $id)
  {
    try {
      $data = $request->validated();
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

  public function assignProducts(AssignProductToShelfRequest $request)
  {
    try {
      return $this->service->assignProducts($request->validated());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function removeProduct(RemoveProductFromShelfRequest $request)
  {
    try {
      return $this->service->removeProduct($request->validated());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getShelfProducts($shelfId)
  {
    try {
      return $this->success($this->service->getShelfProducts($shelfId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}