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
use App\Http\Requests\ap\postventa\taller\ApproveApOrderQuotationsRequest;
use App\Http\Services\ap\postventa\taller\ApOrderQuotationsService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use Exception;
use Illuminate\Http\Request;

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

  public function listForPurchaseRequestTaller(Request $request)
  {
    try {
      return $this->service->listForPurchaseRequestTaller($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function listForPurchaseRequestMeson(Request $request)
  {
    try {
      return $this->service->listForPurchaseRequestMeson($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApOrderQuotationsRequest $request)
  {
    try {
      $this->validateVehicleForQuotation($request->vehicle_id);
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

      // Validar el vehículo si está presente en la actualización
      if (isset($data['vehicle_id'])) {
        $this->validateVehicleForQuotation($data['vehicle_id']);
      }

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
      // Obtener el parámetro show_codes desde la query string (por defecto true)
      $showCodes = request()->query('show_codes', true);

      // Convertir a booleano si viene como string
      if (is_string($showCodes)) {
        $showCodes = filter_var($showCodes, FILTER_VALIDATE_BOOLEAN);
      }

      return $this->service->generateQuotationPDF($id, $showCodes);
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

  public function approveTaller(ApproveApOrderQuotationsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->approveTaller($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function approveRepuesto(ApproveApOrderQuotationsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->approveRepuesto($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updateDeliveryInfo(Request $request, $id)
  {
    try {
      $request->validate([
        'customer_signature_delivery_url' => 'required|string',
        'delivery_document_number' => 'required|string|max:255',
      ]);

      return $this->success($this->service->updateDeliveryInfo($id, $request->only([
        'customer_signature_delivery_url',
        'delivery_document_number',
      ])));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function sendNotificationEmail($id)
  {
    try {
      return $this->success($this->service->sendQuotationNotificationEmail($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function duplicate($id)
  {
    try {
      return $this->success($this->service->duplicate($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function sendVirtualConfirmationLink($id)
  {
    try {
      return $this->success($this->service->sendVirtualConfirmationLink($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function regenerateConfirmationToken($id)
  {
    try {
      return $this->success($this->service->regenerateConfirmationToken($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function invoiceTo(Request $request, $id)
  {
    try {
      $data = $request->validate(
        [
          'invoice_to' => 'required|integer|exists:business_partners,id',
        ]
      );
      $data['id'] = $id;
      return $this->success($this->service->invoiceTo($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function segmentBySupplyType($id)
  {
    try {
      return $this->success($this->service->segmentBySupplyType($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Asocia una guía de remisión a una cotización
   *
   * @param Request $request
   * @param int $id ID de la cotización
   * @return \Illuminate\Http\JsonResponse
   */
  public function associateShippingGuide(Request $request, $id)
  {
    try {
      $request->validate([
        'shipping_guide_id' => 'required|integer|exists:shipping_guides,id',
      ]);

      return $this->success($this->service->associateShippingGuide($id, $request->shipping_guide_id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Desasocia la guía de remisión de una cotización
   *
   * @param int $id ID de la cotización
   * @return \Illuminate\Http\JsonResponse
   */
  public function dissociateShippingGuide($id)
  {
    try {
      return $this->success($this->service->dissociateShippingGuide($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function recalculateTotals($id)
  {
    try {
      return $this->success($this->service->recalculateTotals($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Valida que el vehículo cumpla con los requisitos para crear/actualizar una cotización
   *
   * @param int $vehicleId ID del vehículo a validar
   * @throws Exception Si el vehículo no cumple con los requisitos
   */
  private function validateVehicleForQuotation(int $vehicleId): void
  {
    $vehicle = Vehicles::with([
      'model.family.brand',
      'color'
    ])->find($vehicleId);

    if (!$vehicle) {
      throw new Exception('El vehículo seleccionado no existe.');
    }

    // Validar que el vehículo tenga un modelo asignado
    if (!$vehicle->ap_models_vn_id) {
      throw new Exception('El vehículo debe tener un modelo asignado para crear una cotización.');
    }

    // Validar que el modelo exista y tenga familia
    if (!$vehicle->model) {
      throw new Exception('El modelo del vehículo no existe.');
    }

    if (!$vehicle->model->family) {
      throw new Exception('El modelo del vehículo debe tener una familia asignada.');
    }

    // Validar que la familia tenga marca
    if (!$vehicle->model->family->brand) {
      throw new Exception('La familia del modelo debe tener una marca asignada.');
    }

    // Validar que la marca no sea otros id = 13
    if ($vehicle->model->family->brand->id === ApVehicleBrand::BRAND_OTHERS_ID) {
      throw new Exception('No se puede crear una cotización para vehículos de marca "OTROS".');
    }

    // Validar que el vehículo no tenga color "OTROS" (COLOR_OTHERS_ID = 1003)
    if ($vehicle->vehicle_color_id === ApMasters::COLOR_OTHERS_ID) {
      throw new Exception('No se puede crear una cotización para vehículos con color "OTROS".');
    }
  }
}
