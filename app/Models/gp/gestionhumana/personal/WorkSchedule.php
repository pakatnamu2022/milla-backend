<?php

namespace App\Models\gp\gestionhumana\personal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
  protected $table = 'work_schedules';

  protected $fillable = [
    'name',
    'checkin',
    'lunch_out',
    'lunch_in',
    'checkout',
  ];

  const filters = [
    'id'   => '=',
    'name' => 'like',
  ];

  const sorts = [
    'id',
    'name',
  ];

  public function details(): HasMany
  {
    return $this->hasMany(WorkScheduleDetail::class, 'work_schedule_id')->orderBy('day_of_week');
  }

  public function workers(): HasMany
  {
    return $this->hasMany(Worker::class, 'work_schedule_id');
  }
}
