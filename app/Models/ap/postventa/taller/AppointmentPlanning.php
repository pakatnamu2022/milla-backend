<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AppointmentPlanning extends Model
{
  use softDeletes;

  protected $table = 'appointment_planning';

  protected $fillable = [
    'type_operation_appointment_id',
    'type_planning_id',
    'ap_vehicle_id',
    'advisor_id',
    'description',
    'delivery_date',
    'delivery_time',
    'date_appointment',
    'time_appointment',
    'full_name_client',
    'email_client',
    'phone_client',
    'created_by',
    'is_taken',
    'sede_id',
    'num_doc_client',
    'owner_id',
  ];

  const filters = [
    'search' => ['full_name_client', 'email_client', 'phone_client', 'description', 'vehicle.plate', 'num_doc_client'],
    'type_operation_appointment_id' => '=',
    'type_planning_id' => '=',
    'ap_vehicle_id' => '=',
    'advisor_id' => '=',
    'date_appointment' => 'between',
    'delivery_date' => 'between',
    'created_by' => '=',
    'is_taken' => '=',
    'sede_id' => '=',
    'num_doc_client' => '=',
    'created_at' => 'date_between',
  ];

  const sorts = [
    'date_appointment',
    'delivery_date',
    'created_at',
  ];

  protected $casts = [
    'is_taken' => 'boolean',
    'date_appointment' => 'date',
    'delivery_date' => 'date',
  ];

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper($value);
  }

  public function setFullNameClientAttribute($value)
  {
    $this->attributes['full_name_client'] = Str::upper($value);
  }

  public function setEmailClientAttribute($value)
  {
    $this->attributes['email_client'] = Str::lower($value);
  }

  public function typeOperationAppointment()
  {
    return $this->belongsTo(ApMasters::class, 'type_operation_appointment_id');
  }

  public function typePlanning()
  {
    return $this->belongsTo(TypePlanningWorkOrder::class, 'type_planning_id');
  }

  public function vehicle()
  {
    return $this->belongsTo(Vehicles::class, 'ap_vehicle_id');
  }

  public function advisor()
  {
    return $this->belongsTo(Worker::class, 'advisor_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function owner()
  {
    return $this->belongsTo(BusinessPartners::class, 'owner_id');
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function workOrder()
  {
    return $this->hasOne(ApWorkOrder::class, 'appointment_planning_id');
  }

  // Export Methods
  public static function getReportData($filters = [])
  {
    $query = self::with([
      'vehicle',
      'advisor',
      'sede',
      'typePlanning',
      'typeOperationAppointment',
      'owner',
      'workOrder'
    ]);

    // Apply filters
    foreach ($filters as $filter) {
      $column = $filter['column'];
      $operator = $filter['operator'];
      $value = $filter['value'];

      if ($column === 'advisor_id' && $operator === '=') {
        $query->where('advisor_id', $value);
      } elseif ($column === 'sede_id' && $operator === '=') {
        $query->where('sede_id', $value);
      } elseif ($column === 'created_at' && $operator === 'date_between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('created_at', [$value[0], $value[1]]);
        }
      } elseif ($column === 'date_appointment' && $operator === 'between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('date_appointment', [$value[0], $value[1]]);
        }
      } elseif ($column === 'delivery_date' && $operator === 'between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('delivery_date', [$value[0], $value[1]]);
        }
      } elseif ($column === 'is_taken' && $operator === '=') {
        $query->where('is_taken', $value);
      } elseif ($column === 'type_planning_id' && $operator === '=') {
        $query->where('type_planning_id', $value);
      } elseif ($column === 'type_operation_appointment_id' && $operator === '=') {
        $query->where('type_operation_appointment_id', $value);
      }
    }

    $appointments = $query->get();

    return $appointments->map(function ($appointment) {
      return [
        'placa_vehiculo' => $appointment->vehicle ? $appointment->vehicle->plate : '',
        'vin_vehiculo' => $appointment->vehicle ? $appointment->vehicle->vin : '',
        'cliente' => $appointment->full_name_client,
        'num_documento' => $appointment->num_doc_client,
        'email' => $appointment->email_client,
        'telefono' => $appointment->phone_client,
        'asesor' => $appointment->advisor ? $appointment->advisor->nombre_completo : '',
        'sede' => $appointment->sede ? $appointment->sede->abreviatura : '',
        'tipo_planificacion' => $appointment->typePlanning ? $appointment->typePlanning->description : '',
        'tipo_operacion' => $appointment->typeOperationAppointment ? $appointment->typeOperationAppointment->description : '',
        'fecha_cita' => $appointment->date_appointment ? $appointment->date_appointment->format('Y-m-d') : '',
        'hora_cita' => $appointment->time_appointment,
        'fecha_entrega' => $appointment->delivery_date ? $appointment->delivery_date->format('Y-m-d') : '',
        'hora_entrega' => $appointment->delivery_time,
        'descripcion' => $appointment->description,
        'tomada' => $appointment->is_taken ? 'Sí' : 'No',
        'numero_ot' => ($appointment->is_taken && $appointment->workOrder) ? $appointment->workOrder->correlative : '',
        'fecha_creacion' => $appointment->created_at ? $appointment->created_at->format('Y-m-d H:i:s') : '',
      ];
    });
  }

  public static function getReportableColumns()
  {
    return [
      'placa_vehiculo' => 'Placa Vehículo',
      'vin_vehiculo' => 'VIN Vehículo',
      'cliente' => 'Cliente',
      'num_documento' => 'Número Documento',
      'email' => 'Email',
      'telefono' => 'Teléfono',
      'asesor' => 'Asesor',
      'sede' => 'Sede',
      'tipo_planificacion' => 'Tipo Planificación',
      'tipo_operacion' => 'Tipo Operación',
      'fecha_cita' => 'Fecha Cita',
      'hora_cita' => 'Hora Cita',
      'fecha_entrega' => 'Fecha Entrega',
      'hora_entrega' => 'Hora Entrega',
      'descripcion' => 'Descripción',
      'tomada' => 'Tomada',
      'numero_ot' => 'Número OT',
      'fecha_creacion' => 'Fecha Creación',
    ];
  }

  public static function getReportStyles()
  {
    return [
      'headerBackgroundColor' => '4472C4',
      'headerFontColor' => 'FFFFFF',
      'headerFontSize' => 11,
      'headerBold' => true,
      'bodyFontSize' => 10,
      'freezePane' => 'A2',
      'autoFilter' => true,
    ];
  }
}
