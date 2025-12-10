<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApVehicleInspectionResource;
use App\Http\Services\BaseService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\ap\postventa\taller\ApVehicleInspection;
use App\Models\ap\postventa\taller\ApVehicleInspectionDamages;
use App\Models\gp\gestionsistema\DigitalFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class ApVehicleInspectionService extends BaseService
{
  protected DigitalFileService $digitalFileService;

  // Configuración de rutas para archivos
  private const FILE_PATHS = [
    'damage_photo' => '/ap/postventa/taller/inspecciones/danos/',
  ];

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleInspection::class,
      $request,
      ApVehicleInspection::filters,
      ApVehicleInspection::sorts,
      ApVehicleInspectionResource::class
    );
  }

  public function find($id)
  {
    $inspection = ApVehicleInspection::with([
      'damages',
      'workOrder',
      'inspectionBy'
    ])->where('id', $id)->first();

    if (!$inspection) {
      throw new Exception('Inspección vehicular no encontrada');
    }

    return $inspection;
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      // Set inspected_by if authenticated
      if (auth()->check()) {
        $data['inspected_by'] = auth()->user()->id;
      }

      // Extraer damages del array
      $damages = $data['damages'] ?? [];
      unset($data['damages']);

      // Crear la inspección
      $inspection = ApVehicleInspection::create($data);

      // Procesar y crear los daños con sus imágenes
      if (!empty($damages)) {
        $this->processDamages($inspection, $damages);
      }

      // Marcar la planificación de cita como tomada si se proporcionó
      if (isset($data['appointment_planning_id']) && $data['appointment_planning_id']) {
        $inspection->workOrder->appointmentPlanning->is_taken = true;
        $inspection->workOrder->appointmentPlanning->save();
      }

      DB::commit();

      // Recargar con relaciones
      $inspection->load(['damages', 'workOrder', 'inspectionBy']);

      return new ApVehicleInspectionResource($inspection);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new ApVehicleInspectionResource($this->find($id));
  }

  public function getByWorkOrder($workOrderId)
  {
    $inspection = ApVehicleInspection::with([
      'damages',
      'workOrder',
      'inspectionBy'
    ])->where('work_order_id', $workOrderId)->first();

    if (!$inspection) {
      return response()->json([
        'message' => 'No se encontró inspección para esta orden de trabajo'
      ], 404);
    }

    return new ApVehicleInspectionResource($inspection);
  }

  public function destroy(int $id)
  {
    try {
      DB::beginTransaction();

      $inspection = $this->find($id);

      // Eliminar daños y sus archivos
      $this->deleteInspectionDamages($inspection);

      // Eliminar la inspección
      $inspection->delete();

      DB::commit();

      return response()->json(['message' => 'Inspección vehicular eliminada correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Procesa y crea los daños con sus imágenes
   */
  private function processDamages($inspection, array $damages): void
  {
    foreach ($damages as $damageData) {
      $photoFile = null;

      // Verificar si hay una foto
      if (isset($damageData['photo']) && $damageData['photo'] instanceof UploadedFile) {
        $photoFile = $damageData['photo'];
        unset($damageData['photo']);
      }

      // Crear el daño
      $damage = new ApVehicleInspectionDamages($damageData);
      $damage->vehicle_inspection_id = $inspection->id;

      // Si hay foto, subirla
      if ($photoFile) {
        $path = self::FILE_PATHS['damage_photo'];
        $model = $damage->getTable();

        // Subir archivo usando DigitalFileService
        $digitalFile = $this->digitalFileService->store($photoFile, $path, 'public', $model);

        // Asignar la URL al daño
        $damage->photo_url = $digitalFile->url;
      }

      $damage->save();
    }
  }

  /**
   * Elimina los daños de una inspección y sus archivos asociados
   */
  private function deleteInspectionDamages($inspection): void
  {
    foreach ($inspection->damages as $damage) {
      // Eliminar archivo si existe
      if ($damage->photo_url) {
        $digitalFile = DigitalFile::where('url', $damage->photo_url)->first();

        if ($digitalFile) {
          $this->digitalFileService->destroy($digitalFile->id);
        }
      }

      // Eliminar el daño
      $damage->delete();
    }
  }
}
