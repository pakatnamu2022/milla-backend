<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApVehicleInspectionResource;
use App\Http\Services\BaseService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Utils\Helpers;
use App\Models\ap\postventa\taller\ApVehicleInspection;
use App\Models\ap\postventa\taller\ApVehicleInspectionDamages;
use App\Models\gp\gestionhumana\personal\WorkerSignature;
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
    'customer_signature' => '/ap/postventa/taller/inspecciones/firmas-cliente/',
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

      // Extraer firmas en base64 del array
      $customerSignature = $data['customer_signature'] ?? null;
      unset($data['customer_signature']);

      // Extraer damages del array
      $damages = $data['damages'] ?? [];
      unset($data['damages']);

      // Crear la inspección
      $inspection = ApVehicleInspection::create($data);

      // Procesar y guardar firmas si existen
      if ($customerSignature) {
        $this->processSignature($inspection, $customerSignature, 'customer');
      }

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

      // Eliminar firmas si existen
      $this->deleteSignatures($inspection);

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

  /**
   * Elimina las firmas de una inspección
   */
  private function deleteSignatures($inspection): void
  {
    // Eliminar firma del cliente si existe
    if ($inspection->customer_signature_url) {
      $digitalFile = DigitalFile::where('url', $inspection->customer_signature_url)->first();

      if ($digitalFile) {
        $this->digitalFileService->destroy($digitalFile->id);
      }
    }
  }

  /**
   * Procesa una firma en base64 y la guarda en Digital Ocean
   */
  private function processSignature($inspection, string $base64Signature, string $type): void
  {
    // Convertir base64 a UploadedFile con recorte automático
    $signatureFile = Helpers::base64ToUploadedFile($base64Signature, "{$type}_signature.png");

    // Determinar la ruta y campo según el tipo
    $path = self::FILE_PATHS["{$type}_signature"];
    $model = $inspection->getTable();
    $fieldName = "{$type}_signature_url";

    // Subir archivo usando DigitalFileService
    $digitalFile = $this->digitalFileService->store($signatureFile, $path, 'public', $model);

    // Actualizar la inspección con la URL
    $inspection->{$fieldName} = $digitalFile->url;
    $inspection->save();
  }

  /**
   * Genera el reporte de recepción en PDF
   */
  public function generateReceptionReport($id)
  {
    // Obtener la inspección con todas las relaciones necesarias
    $inspection = ApVehicleInspection::with([
      'damages',
      'workOrder.vehicle.model.family.brand',
      'workOrder.vehicle.color',
      'workOrder.vehicle.customer',
      'workOrder.advisor', // Worker extiende de Person, no tiene relación person
      'workOrder.sede',
      'workOrder.status',
      'workOrder.items.typePlanning',
      'workOrder.appointmentPlanning',
      'inspectionBy.person' // User sí tiene relación person
    ])->findOrFail($id);

    $workOrder = $inspection->workOrder;
    $vehicle = $workOrder->vehicle;
    $customer = $vehicle->customer;
    $advisor = $workOrder->advisor; // Worker extiende de Person directamente

    // Obtener firma del asesor desde WorkerSignature
    // El asesor es Worker que extiende de Person directamente
    $advisorSignature = null;
    if ($advisor) {
      $workerSignature = WorkerSignature::where('worker_id', $advisor->id)->first();
      if ($workerSignature && $workerSignature->signature_url) {
        $advisorSignature = Helpers::convertUrlToBase64($workerSignature->signature_url);
      }
    }

    // Convertir firma del cliente a base64 si existe
    $customerSignature = null;
    if ($inspection->customer_signature_url) {
      $customerSignature = Helpers::convertUrlToBase64($inspection->customer_signature_url);
    }

    // Preparar lista de checks del inventario
    $inventoryChecks = [
      'dirty_unit' => 'UNIDAD SUCIA',
      'unit_ok' => 'UNIDAD OK',
      'title_deed' => 'TARJETA DE PROPIEDAD',
      'soat' => 'SOAT',
      'moon_permits' => 'PERMISOS LUNETA',
      'service_card' => 'TARJETA DE SERVICIO',
      'owner_manual' => 'MANUAL DEL PROPIETARIO',
      'key_ring' => 'LLAVERO',
      'wheel_lock' => 'SEGURO DE RUEDA',
      'safe_glasses' => 'GAFAS DE SEGURIDAD',
      'radio_mask' => 'MÁSCARA DE RADIO',
      'lighter' => 'ENCENDEDOR',
      'floors' => 'PISOS',
      'seat_cover' => 'CUBRE ASIENTOS',
      'quills' => 'PLUMILLAS',
      'antenna' => 'ANTENA',
      'glasses_wheel' => 'VASOS RUEDA',
      'emblems' => 'EMBLEMAS',
      'spare_tire' => 'LLANTA DE REPUESTO',
      'fluid_caps' => 'TAPAS DE FLUIDOS',
      'tool_kit' => 'KIT DE HERRAMIENTAS',
      'jack_and_lever' => 'GATO Y PALANCA',
    ];

    // Preparar datos para la vista
    $data = [
      'inspection' => $inspection,
      'workOrder' => $workOrder,
      'vehicle' => $vehicle,
      'customer' => $customer,
      'advisor' => $advisor, // Worker ya es Person directamente
      'advisorPhone' => $advisor ? $advisor->cel_personal : '',
      'sede' => $workOrder->sede,
      'status' => $workOrder->status,
      'items' => $workOrder->items,
      'damages' => $inspection->damages,
      'inventoryChecks' => $inventoryChecks,
      'customerSignature' => $customerSignature,
      'advisorSignature' => $advisorSignature,
      'appointmentNumber' => $workOrder->appointmentPlanning ? $workOrder->appointmentPlanning->correlative : 'N/A',
      'isGuarantee' => $workOrder->is_guarantee ?? false,
      'isRecall' => $workOrder->is_recall ?? false,
    ];

    // Generar PDF
    $pdf = \PDF::loadView('reports.ap.postventa.taller.reception-report', $data);
    $pdf->setPaper('a4', 'portrait');

    return $pdf->stream("reporte-recepcion-{$workOrder->correlative}.pdf");
  }
}
