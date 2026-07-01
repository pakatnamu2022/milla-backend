<?php

namespace App\Models\gp\gestionhumana\asistencias;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceExclusion extends Model
{
  protected $table = 'attendance_exclusions';

  protected $fillable = [
    'person_id',
    'reason',
    'active',
    'created_by',
  ];

  protected $casts = [
    'active' => 'boolean',
  ];

  const filters = [
    'search'    => ['person.nombre_completo'],
    'person_id' => '=',
    'active'    => '=',
  ];

  const sorts = ['id', 'person_id', 'active', 'created_at'];

  public function person(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'person_id');
  }
}
