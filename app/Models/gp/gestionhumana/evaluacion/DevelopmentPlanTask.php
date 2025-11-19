<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DevelopmentPlanTask extends Model
{
  use SoftDeletes;

  protected $table = 'development_plan_task';

  protected $fillable = [
    'description',
    'end_date',
    'fulfilled',
    'detailed_development_plan_id',
  ];

  protected $casts = [
    'end_date' => 'date',
    'fulfilled' => 'boolean',
  ];

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper($value);
  }

  public function detailedDevelopmentPlan()
  {
    return $this->belongsTo(DetailedDevelopmentPlan::class, 'detailed_development_plan_id');
  }
}
