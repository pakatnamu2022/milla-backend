<?php

namespace App\Models\gp\gestionhumana\asistencias;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSync extends Model
{
  protected $table = 'attendance_sync';

  protected $fillable = [
    'zkbio_transaction_id',
    'person_id',
    'emp_code',
    'full_name',
    'date',
    'mark_type',
    'time',
    'area',
    'punch_state_original',
    'synced_at',
  ];

  const filters = [
    'search'         => ['emp_code', 'full_name', 'person.nombre_completo'],
    'emp_code'       => '=',
    'person_id'      => '=',
    'mark_type'      => '=',
    'date'           => '=',
    'date_from'      => 'scope',
    'date_to'        => 'scope',
    'person.sede_id' => '='
  ];

  const sorts = [
    'id',
    'date',
    'emp_code',
    'time',
    'mark_type',
    'synced_at',
  ];

  protected $casts = [
    'date'      => 'date',
    'synced_at' => 'datetime',
  ];

  public function scopeDateFrom(Builder $query, string $value): Builder
  {
    return $query->whereDate('date', '>=', $value);
  }

  public function scopeDateTo(Builder $query, string $value): Builder
  {
    return $query->whereDate('date', '<=', $value);
  }

  public function person(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'person_id');
  }
}
