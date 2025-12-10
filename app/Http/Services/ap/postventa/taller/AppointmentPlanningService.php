<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\AppointmentPlanningResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentPlanningService extends BaseService implements BaseServiceInterface
{
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
      $data['advisor_id'] = auth()->user()->person?->id;
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

    if (!$startDate || !$endDate) {
      throw new Exception('Se requieren start_date y end_date');
    }

    // Configuración de horario de trabajo
    $workStart = 8; // 8:00 AM
    $workEnd = 18; // 6:00 PM
    $slotInterval = 15; // minutos

    $availableSlots = [];

    // Obtener todas las citas con date_appointment en el rango de fechas
    $appointmentSlots = AppointmentPlanning::whereBetween('date_appointment', [$startDate, $endDate])
      ->whereNull('deleted_at')
      ->get()
      ->mapWithKeys(function ($item) {
        // Formatear time_appointment a HH:MM para comparación
        $time = substr($item->time_appointment, 0, 5); // Toma solo HH:MM de HH:MM:SS
        $key = $item->date_appointment . '_' . $time;
        return [$key => $item];
      });

    // Obtener todas las citas con delivery_date en el rango de fechas
    $deliverySlots = AppointmentPlanning::whereBetween('delivery_date', [$startDate, $endDate])
      ->whereNull('deleted_at')
      ->get()
      ->mapWithKeys(function ($item) {
        // Formatear delivery_time a HH:MM para comparación
        $time = substr($item->delivery_time, 0, 5); // Toma solo HH:MM de HH:MM:SS
        $key = $item->delivery_date . '_' . $time;
        return [$key => $item];
      });

    // Combinar ambos tipos de slots ocupados
    $occupiedSlots = $appointmentSlots->union($deliverySlots);

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

          $appointment = $occupiedSlots->get($key);

          $daySlots[] = [
            'date' => $dateStr,
            'time' => $timeStr,
            'available' => !$appointment,
            'appointment_id' => $appointment ? $appointment->id : null,
            'type' => $appointment ? (
            $appointment->date_appointment == $dateStr && substr($appointment->time_appointment, 0, 5) == $timeStr
              ? 'Reservación'
              : 'Entrega'
            ) : null,
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
    $clientAddress = 'N/A';
    $clientUbigeo = 'N/A';
    $clientCity = 'N/A';

    if ($customer) {
      $clientDocument = $customer->num_doc ?? 'N/A';
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
      'full_name_client' => $appointmentPlanning->full_name_client,
      'email_client' => $appointmentPlanning->email_client,
      'phone_client' => $appointmentPlanning->phone_client,
      'client_document' => $clientDocument,
      'client_address' => $clientAddress,
      'client_ubigeo' => $clientUbigeo,
      'client_city' => $clientCity,
      'description' => $appointmentPlanning->description,
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
}
