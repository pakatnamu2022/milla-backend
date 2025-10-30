<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexVehiclesRequest;
use App\Http\Requests\ap\comercial\RegularizeAnticiposRequest;
use App\Http\Requests\ap\comercial\StoreVehiclesRequest;
use App\Http\Requests\ap\comercial\UpdateVehiclesRequest;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Services\ap\comercial\VehicleService;
use App\Http\Services\ap\facturacion\ElectronicDocumentService;
use App\Http\Traits\HasApiResponse;
use App\Models\ap\comercial\ApVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VehiclesController extends Controller
{
  use HasApiResponse;

  protected VehicleService $service;
  protected ElectronicDocumentService $electronicDocumentService;

  public function __construct(
    VehicleService $service,
    ElectronicDocumentService $electronicDocumentService
  ) {
    $this->service = $service;
    $this->electronicDocumentService = $electronicDocumentService;
  }

  /**
   * Export vehicles data
   * @param Request $request
   * @return JsonResponse
   */
  public function export(Request $request)
  {
    try {
//      return $this->service->export($request);
      return true;
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display a listing of vehicles with filters
   *
   * @param IndexVehiclesRequest $request
   * @return JsonResponse
   */
  public function index(IndexVehiclesRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created vehicle
   *
   * @param StoreVehiclesRequest $request
   * @return JsonResponse
   */
  public function store(StoreVehiclesRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified vehicle
   *
   * @param int $id
   * @return JsonResponse
   */
  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified vehicle
   *
   * @param UpdateVehiclesRequest $request
   * @param int $id
   * @return JsonResponse
   */
  public function update(UpdateVehiclesRequest $request, int $id): JsonResponse
  {
    try {
      $data = array_merge($request->validated(), ['id' => $id]);
      return $this->success($this->service->update($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified vehicle (soft delete)
   *
   * @param int $id
   * @return JsonResponse
   */
  public function destroy(int $id): JsonResponse
  {
    try {
      ;
      return $this->success($this->service->destroy($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get pending anticipos for a vehicle
   *
   * @param int $id
   * @return JsonResponse
   */
  public function getPendingAnticipos(int $id): JsonResponse
  {
    try {
      $vehicle = ApVehicle::findOrFail($id);

      $anticipos = $this->electronicDocumentService->getPendingAnticipos(
        'comercial',
        'vehicle_order',
        $id
      );

      return $this->success([
        'vehicle_id' => $vehicle->id,
        'vehicle_vin' => $vehicle->vin,
        'anticipos' => $anticipos,
        'total_anticipos' => $anticipos->sum('total'),
        'count' => $anticipos->count(),
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Regularize anticipos creating final invoice
   *
   * @param RegularizeAnticiposRequest $request
   * @param int $id
   * @return JsonResponse
   */
  public function regularizeAnticipos(RegularizeAnticiposRequest $request, int $id): JsonResponse
  {
    try {
      $vehicle = ApVehicle::with('model')->findOrFail($id);

      if (!$vehicle->model || !$vehicle->model->sale_price) {
        return $this->error('El vehículo no tiene un precio de venta definido');
      }

      // Obtener anticipos
      $anticipos = $this->electronicDocumentService->getPendingAnticipos(
        'comercial',
        'vehicle_order',
        $id
      )->whereIn('id', $request->input('anticipo_ids'));

      if ($anticipos->isEmpty()) {
        return $this->error('No se encontraron anticipos válidos para regularizar');
      }

      // Construir items de regularización
      $items = $this->electronicDocumentService->buildRegularizationItems(
        $vehicle,
        $anticipos,
        $request->only([
          'sunat_concept_igv_type_id',
          'unidad_de_medida',
        ])
      );

      // Calcular totales
      $totals = $this->electronicDocumentService->calculateRegularizationTotals(
        (float) $vehicle->model->sale_price,
        $anticipos
      );

      // Crear documento electrónico
      $documentData = array_merge(
        $request->validated(),
        $totals,
        [
          'origin_module' => 'comercial',
          'origin_entity_type' => 'vehicle_order',
          'origin_entity_id' => $vehicle->id,
          'placa_vehiculo' => $vehicle->license_plate,
        ]
      );

      $document = $this->electronicDocumentService->createDocument($documentData, $items);

      return $this->success([
        'message' => 'Factura de regularización creada exitosamente',
        'document' => $document,
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
