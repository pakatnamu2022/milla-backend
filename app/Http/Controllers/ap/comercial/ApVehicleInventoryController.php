<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexApVehicleInventoryRequest;
use App\Http\Requests\ap\comercial\StoreApVehicleInventoryRequest;
use App\Http\Requests\ap\comercial\UpdateApVehicleInventoryRequest;
use App\Http\Services\ap\comercial\ApVehicleInventoryService;
use App\Http\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ApVehicleInventoryController extends Controller
{
  use HasApiResponse;

  protected ApVehicleInventoryService $service;

  public function __construct(ApVehicleInventoryService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApVehicleInventoryRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApVehicleInventoryRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateApVehicleInventoryRequest $request, int $id): JsonResponse
  {
    try {
      $data = array_merge($request->validated(), ['id' => $id]);
      return $this->success($this->service->update($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Evalúa un registro de inventario y opcionalmente confirma su ubicación.
   * Si is_evaluated=true y is_location_confirmed=true, el vehículo pasa a estado INVENTARIO VN.
   */
  public function evaluate(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'is_location_confirmed' => 'required|boolean',
    ]);

    try {
      return $this->success(
        $this->service->evaluate($id, $request->boolean('is_location_confirmed'))
      );
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Importa vehículos al inventario desde un archivo Excel.
   */
  public function import(Request $request): JsonResponse
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
      return $this->errorValidation('Archivo no válido. Asegúrate de enviar un archivo Excel con el campo "file".');
    }

    try {
      $result = $this->service->importFromExcel($request->file('file'));

      if ($result['success']) {
        return $this->success($result, $result['message'] ?? 'Importación completada');
      }

      return $this->success($result);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Descarga la plantilla Excel para la importación de inventario.
   */
  public function downloadTemplate()
  {
    try {
      return $this->service->downloadTemplate();
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
