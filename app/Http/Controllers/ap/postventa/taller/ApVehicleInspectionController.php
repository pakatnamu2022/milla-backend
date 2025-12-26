<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApVehicleInspectionRequest;
use App\Http\Requests\ap\postventa\taller\StoreApVehicleInspectionRequest;
use App\Http\Services\ap\postventa\taller\ApVehicleInspectionService;
use Exception;

class ApVehicleInspectionController extends Controller
{
  protected ApVehicleInspectionService $service;

  public function __construct(ApVehicleInspectionService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of the resource.
   */
  public function index(IndexApVehicleInspectionRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al listar las inspecciones vehiculares',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(StoreApVehicleInspectionRequest $request)
  {
    try {
      $data = $request->validated();

      // Procesar los daños con sus imágenes si existen
      if ($request->has('damages')) {
        $damages = [];
        $damagesInput = $request->input('damages', []);

        foreach ($damagesInput as $index => $damageData) {
          $damage = $damageData;

          // Agregar la foto si existe
          if ($request->hasFile("damages.{$index}.photo")) {
            $damage['photo'] = $request->file("damages.{$index}.photo");
          }

          $damages[] = $damage;
        }

        $data['damages'] = $damages;
      }

      return $this->service->store($data);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al crear la inspección vehicular',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Display the specified resource.
   */
  public function show($id)
  {
    try {
      return $this->service->show($id);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al obtener la inspección vehicular',
        'error' => $e->getMessage()
      ], 404);
    }
  }

  /**
   * Get inspection by work order ID.
   */
  public function getByWorkOrder($workOrderId)
  {
    try {
      return $this->service->getByWorkOrder($workOrderId);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al obtener la inspección vehicular',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al eliminar la inspección vehicular',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Generate reception report PDF.
   */
  public function generateReceptionReport($id)
  {
    try {
      return $this->service->generateReceptionReport($id);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al generar el reporte de recepción',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
