<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\AssignVehicleToQuoteRequest;
use App\Http\Requests\ap\comercial\IndexPurchaseRequestQuoteRequest;
use App\Http\Requests\ap\comercial\StorePurchaseRequestQuoteRequest;
use App\Http\Requests\ap\comercial\UpdatePurchaseRequestQuoteRequest;
use App\Http\Requests\ap\comercial\UpdateVehiclesRequest;
use App\Http\Services\ap\comercial\PurchaseRequestQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use function array_merge;

class PurchaseRequestQuoteController extends Controller
{
  protected PurchaseRequestQuoteService $service;

  public function __construct(PurchaseRequestQuoteService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPurchaseRequestQuoteRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StorePurchaseRequestQuoteRequest $request)
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

  public function update(UpdatePurchaseRequestQuoteRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function assignVehicle(AssignVehicleToQuoteRequest $request, int $id): JsonResponse
  {
    try {
      $data = array_merge($request->validated(), ['id' => $id]);
      return $this->success($this->service->assignVehicle($data));
    } catch (Throwable $th) {
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

  public function reportPDF(Request $request, $id)
  {
    try {
      $data = $request->all();
      $data['id'] = $id;

      $pdf = $this->service->generateReportPDF($data);

      $filename = "metas.pdf";

      return $pdf->download($filename);

    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
