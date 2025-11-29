<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApPostVentaMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\gp\gestionhumana\personal\Worker;
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
  ];

  const filters = [
    'search' => ['full_name_client', 'email_client', 'phone_client', 'description'],
    'type_operation_appointment_id' => '=',
    'type_planning_id' => '=',
    'ap_vehicle_id' => '=',
    'advisor_id' => '=',
    'delivery_date' => 'between',
    'created_by' => '=',
  ];

  const sorts = [
    'date_appointment',
    'delivery_date',
    'created_at',
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
    return $this->belongsTo(ApPostVentaMasters::class, 'type_operation_appointment_id');
  }

  public function typePlanning()
  {
    return $this->belongsTo(ApPostVentaMasters::class, 'type_planning_id');
  }

  public function vehicle()
  {
    return $this->belongsTo(Vehicles::class, 'ap_vehicle_id');
  }

  public function advisor()
  {
    return $this->belongsTo(Worker::class, 'advisor_id');
  }
}
