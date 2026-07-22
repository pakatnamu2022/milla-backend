<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\AppointmentPlanningResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentPlanningService extends BaseService implements BaseServiceInterface
{
  protected ExportService $exportService;

  public function __construct(ExportService $exportService)
  {
    $this->exportService = $exportService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      AppointmentPlanning::class,
      $request,
      AppointmentPlanning::filters,
      AppointmentPlanning::sorts,
      AppointmentPlanningResource::class,
    );
  }

  public function find($id)
  {
    $appointmentPlanning = AppointmentPlanning::where('id', $id)->first();
    if (!$appointmentPlanning) {
      throw new Exception('Planificación de cita no encontrada');
    }
    return $appointmentPlanning;
  }

  public function store(mixed $data)
  {
    if (auth()->check()) {
      $data['created_by'] = auth()->user()->id;
    }

    if (empty($data['advisor_id'])) {
      $data['advisor_id'] = auth()->user()->person?->id;
    }

    $vehicle = isset($data['ap_vehicle_id']) ? Vehicles::find($data['ap_vehicle_id']) : null;

    if ($vehicle === null) {
      throw new Exception('Vehículo no encontrado');
    }

    if ($vehicle) {
      $data['owner_id'] = $vehicle->customer_id;
    }

    $appointmentPlanning = AppointmentPlanning::create($data);
    return new AppointmentPlanningResource($appointmentPlanning);
  }

  public function show($id)
  {
    return new AppointmentPlanningResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $appointmentPlanning = $this->find($data['id']);

    if ($appointmentPlanning->is_taken) {
      throw new Exception('La cita ya ha sido tomada y no puede ser modificada');
    }

    if (empty($data['advisor_id'])) {
      unset($data['advisor_id']);
    }

    $vehicle = isset($data['ap_vehicle_id']) ? Vehicles::find($data['ap_vehicle_id']) : null;

    if ($vehicle === null) {
      throw new Exception('Vehículo no encontrado');
    }

    if ($vehicle) {
      $data['owner_id'] = $vehicle->customer_id;
    }

    $appointmentPlanning->update($data);
    return new AppointmentPlanningResource($appointmentPlanning);
  }

  public function destroy($id)
  {
    $appointmentPlanning = $this->find($id);

    if ($appointmentPlanning->is_taken) {
      throw new Exception('La cita ya ha sido tomada y no puede ser modificada');
    }

    DB::transaction(function () use ($appointmentPlanning) {
      $appointmentPlanning->delete();
    });
    return response()->json(['message' => 'Planificación de cita eliminada correctamente']);
  }

  public function getAvailableSlots(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $advisor_id = auth()->user()->person->id ?? null;

    if (!$startDate || !$endDate) {
      throw new Exception('Se requieren start_date y end_date');
    }

    if ($advisor_id === null) {
      throw new Exception('Asesor no autenticado');
    }

    // Configuración de horario de trabajo
    $workStart = 8; // 8:00 AM
    $workEnd = 18; // 6:00 PM
    $slotInterval = 15; // minutos

    $availableSlots = [];

    // Obtener todas las CITAS (date_appointment) en el rango de fechas
    $appointments = AppointmentPlanning::whereBetween('date_appointment', [$startDate, $endDate])
      ->where('advisor_id', $advisor_id)
      ->whereNull('deleted_at')
      ->get()
      ->mapWithKeys(function ($item) {
        $time = substr($item->time_appointment, 0, 5);
        $date = $item->date_appointment->format('Y-m-d');
        $key = $date . '_' . $time;
        return [$key => $item];
      });

    // Obtener todas las ENTREGAS (delivery_date) en el rango de fechas
    $deliveries = AppointmentPlanning::whereBetween('delivery_date', [$startDate, $endDate])
      ->where('advisor_id', $advisor_id)
      ->whereNull('deleted_at')
      ->get()
      ->groupBy(function ($item) {
        $time = substr($item->delivery_time, 0, 5);
        $date = $item->delivery_date->format('Y-m-d');
        return $date . '_' . $time;
      });

    // Iterar sobre cada día en el rango
    $start = new \DateTime($startDate);
    $end = new \DateTime($endDate);

    while ($start <= $end) {
      $dateStr = $start->format('Y-m-d');
      $daySlots = [];

      // Generar slots para cada hora
      for ($hour = $workStart; $hour < $workEnd; $hour++) {
        for ($minute = 0; $minute < 60; $minute += $slotInterval) {
          $timeStr = sprintf('%02d:%02d', $hour, $minute);
          $key = $dateStr . '_' . $timeStr;

          // Obtener cita (solo puede haber 1)
          $appointment = $appointments->get($key);

          // Obtener entregas (puede haber múltiples)
          $slotDeliveries = $deliveries->get($key, collect());
          $deliveriesCount = $slotDeliveries->count();

          // Determinar el tipo
          $type = null;
          if ($appointment && $deliveriesCount > 0) {
            $type = 'Ambos';
          } elseif ($appointment) {
            $type = 'Reservación';
          } elseif ($deliveriesCount > 0) {
            $type = 'Entrega';
          }

          $daySlots[] = [
            'date' => $dateStr,
            'time' => $timeStr,
            'available' => !$appointment, // disponible para nueva CITA
            'deliveries_count' => $deliveriesCount,
            'type' => $type,
            'advisor_id' => $appointment ? $appointment->advisor_id : null,
            'advisor_name' => $appointment ? $appointment->advisor?->nombre_completo : null,
          ];
        }
      }

      $availableSlots[] = [
        'date' => $dateStr,
        'slots' => $daySlots,
      ];

      $start->modify('+1 day');
    }

    return response()->json($availableSlots);
  }

  public function generateAppointmentPDF($id)
  {
    $appointmentPlanning = AppointmentPlanning::with([
      'advisor',
      'vehicle.model.family.brand',
      'vehicle.color',
      'vehicle.customer.district.province'
    ])->find($id);

    if (!$appointmentPlanning) {
      throw new Exception('Planificación de cita no encontrada');
    }

    $customer = $appointmentPlanning->vehicle->customer ?? null;

    // Preparar datos del cliente
    $clientDocument = 'N/A';
    $clientName  =  'N/A';
    $clientAddress = 'N/A';
    $clientUbigeo = 'N/A';
    $clientCity = 'N/A';

    if ($customer) {
      $clientDocument = $customer->num_doc ?? 'N/A';
      $clientName = $customer->full_name ?? 'N/A';
      $clientAddress = $customer->direction ?? 'N/A';
      $clientUbigeo = $customer->district->ubigeo ?? 'N/A';
      $clientCity = $customer->district && $customer->district->province
        ? $customer->district->province->name
        : 'N/A';
    }

    // Preparar datos para la vista
    $data = [
      'id' => $appointmentPlanning->id,
      'date_appointment' => $appointmentPlanning->date_appointment,
      'time_appointment' => $appointmentPlanning->time_appointment,
      'delivery_date' => $appointmentPlanning->delivery_date,
      'delivery_time' => $appointmentPlanning->delivery_time,
      'advisor_name' => $appointmentPlanning->advisor ? $appointmentPlanning->advisor->nombre_completo : 'N/A',
      'created_at' => $appointmentPlanning->created_at,
      'full_name_client' => $clientName,
      'email_client' => $appointmentPlanning->email_client,
      'phone_client' => $appointmentPlanning->phone_client,
      'client_document' => $clientDocument,
      'client_address' => $clientAddress,
      'client_ubigeo' => $clientUbigeo,
      'client_city' => $clientCity,
      'description' => $appointmentPlanning->description,
      'planificacion' => $appointmentPlanning->typePlanning ? $appointmentPlanning->typePlanning->description : 'N/A',
      'operacion' => $appointmentPlanning->typeOperationAppointment ? $appointmentPlanning->typeOperationAppointment->description : 'N/A',
    ];

    // Datos del vehículo si existe
    if ($appointmentPlanning->vehicle) {
      $vehicle = $appointmentPlanning->vehicle;
      $data['plate'] = $vehicle->plate;
      $data['vehicle_vin'] = $vehicle->vin;
      $data['vehicle_year'] = $vehicle->year;
      $data['vehicle_brand'] = $vehicle->model && $vehicle->model->family && $vehicle->model->family->brand
        ? $vehicle->model->family->brand->name
        : 'N/A';
      $data['vehicle_version'] = $vehicle->model ? $vehicle->model->version : 'N/A';
      $data['vehicle_color'] = $vehicle->color ? $vehicle->color->description : 'N/A';
    } else {
      $data['plate'] = 'N/A';
      $data['vehicle_vin'] = 'N/A';
      $data['vehicle_year'] = 'N/A';
      $data['vehicle_brand'] = 'N/A';
      $data['vehicle_version'] = 'N/A';
      $data['vehicle_color'] = 'N/A';
    }

    // Generar PDF
    $pdf = Pdf::loadView('reports.ap.postventa.taller.appointment-planning', [
      'appointment' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Agenda_Reserva_' . str_pad($appointmentPlanning->id, 6, '0', STR_PAD_LEFT) . '.pdf';

    return $pdf->download($fileName);
  }

  /**
   * Export appointments to Excel
   */
  public function exportAppointments(Request $request)
  {
    $filters = [];

    // Apply filters from request
    if ($request->filled('advisor_id')) {
      $filters[] = [
        'column' => 'advisor_id',
        'operator' => '=',
        'value' => $request->advisor_id
      ];
    }

    if ($request->filled('sede_id')) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $request->sede_id
      ];
    }

    if ($request->filled('created_at')) {
      $filters[] = [
        'column' => 'created_at',
        'operator' => 'date_between',
        'value' => $request->created_at
      ];
    }

    if ($request->filled('date_appointment')) {
      $filters[] = [
        'column' => 'date_appointment',
        'operator' => 'between',
        'value' => $request->date_appointment
      ];
    }

    if ($request->filled('delivery_date')) {
      $filters[] = [
        'column' => 'delivery_date',
        'operator' => 'between',
        'value' => $request->delivery_date
      ];
    }

    if ($request->filled('is_taken')) {
      $filters[] = [
        'column' => 'is_taken',
        'operator' => '=',
        'value' => $request->is_taken
      ];
    }

    if ($request->filled('type_planning_id')) {
      $filters[] = [
        'column' => 'type_planning_id',
        'operator' => '=',
        'value' => $request->type_planning_id
      ];
    }

    if ($request->filled('type_operation_appointment_id')) {
      $filters[] = [
        'column' => 'type_operation_appointment_id',
        'operator' => '=',
        'value' => $request->type_operation_appointment_id
      ];
    }

    $title = $request->get('title', 'Reporte de Planificación de Citas');

    $options = [
      'title' => $title,
      'filters' => $filters,
      'format' => $request->get('format', 'excel'),
    ];

    return $this->exportService->exportToExcel(AppointmentPlanning::class, $options);
  }
}
