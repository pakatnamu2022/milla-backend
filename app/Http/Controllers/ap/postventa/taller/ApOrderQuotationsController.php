<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApOrderQuotationsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApOrderQuotationsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApOrderQuotationWithProductsRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApOrderQuotationsRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApOrderQuotationWithProductsRequest;
use App\Http\Requests\ap\postventa\taller\DiscardApOrderQuotationsRequest;
use App\Http\Requests\ap\postventa\taller\ConfirmApOrderQuotationsRequest;
use App\Http\Services\ap\postventa\taller\ApOrderQuotationsService;

class ApOrderQuotationsController extends Controller
{
  protected ApOrderQuotationsService $service;

  public function __construct(ApOrderQuotationsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApOrderQuotationsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApOrderQuotationsRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function storeWithProducts(StoreApOrderQuotationWithProductsRequest $request)
  {
    try {
      return $this->success($this->service->storeWithProducts($request->validated()));
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

  public function update(UpdateApOrderQuotationsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updateWithProducts(UpdateApOrderQuotationWithProductsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->updateWithProducts($data));
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

  public function downloadPDF($id)
  {
    try {
      return $this->service->generateQuotationPDF($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function downloadRepuestoPDF($id)
  {
    try {
      // Obtener el parámetro show_codes desde la query string (por defecto true)
      $showCodes = request()->query('show_codes', true);

      // Convertir a booleano si viene como string
      if (is_string($showCodes)) {
        $showCodes = filter_var($showCodes, FILTER_VALIDATE_BOOLEAN);
      }

      return $this->service->generateQuotationRepuestoPDF($id, $showCodes);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function discard(DiscardApOrderQuotationsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->discard($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function confirm(ConfirmApOrderQuotationsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->confirm($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function approve($id)
  {
    try {
      return $this->success($this->service->approve($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
