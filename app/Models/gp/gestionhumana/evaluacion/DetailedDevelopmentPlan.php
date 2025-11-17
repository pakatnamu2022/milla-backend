<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DetailedDevelopmentPlan extends Model
{
    use SoftDeletes;

    protected $table = 'detailed_development_plan';

    protected $fillable = [
        'description',
        'boss_confirms',
        'worker_confirms',
        'boss_confirms_completion',
        'worker_confirms_completion',
        'worker_id',
        'boss_id',
        'gh_evaluation_id',
    ];

    const filters = [
      'search' => ['description', 'worker.nombre_completo', 'boss.nombre_completo'],
      'worker_id' => '=',
      'boss_id' => '=',
      'gh_evaluation_id' => '=',
    ];

    const sorts = [
      'id',
      'description',
      'worker_id',
      'boss_id',
      'gh_evaluation_id',
      'created_at',
      'updated_at',
    ];

    public function setDescriptionAttribute($value)
    {
      if ($value) {
        $this->attributes['description'] = Str::upper($value);
      }
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'gh_evaluation_id');
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function boss()
    {
        return $this->belongsTo(Worker::class, 'boss_id');
    }
}
