<?php

namespace App\Models\gp\gestionhumana\personal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleDetail extends Model
{
  protected $table = 'work_schedule_details';

  public $timestamps = false;

  protected $fillable = [
    'work_schedule_id',
    'day_of_week',
    'checkin',
    'lunch_out',
    'lunch_in',
    'checkout',
  ];

  public function schedule(): BelongsTo
  {
    return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
  }
}
