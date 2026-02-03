<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Support\Str;

class Position extends BaseModel
{
  protected $table = 'rrhh_cargo';

  public $timestamps = false;

  protected $fillable = [
    'name',
    'descripcion',
    'area_id',
    'ntrabajadores', // número de trabajadores, default 0
    'banda_salarial_min',
    'banda_salarial_media',
    'banda_salarial_max',
    'cargo_id', // cargo de liderazgo
    'tipo_onboarding_id',
    'plazo_proceso_seleccion',
    'presupuesto',
    'mof_adjunto',
    'fileadic1',
    'fileadic2',
    'fileadic3',
    'fileadic4',
    'fileadic5',
    'fileadic6',
    'perfil_id',
    'write_id',
    'created_at',
    'updated_at',
    'status_deleted', // 1 activo, 0 eliminado
    'per_diem_category_id'
  ];

  const filters = [
    'search' => ['name', 'descripcion'],
    'name' => 'like',
    'descripcion' => 'like',
    'status_deleted' => '=',
    'area_id' => '=',
    'ntrabajadores' => '=',
    'banda_salarial_min' => '=',
    'banda_salarial_media' => '=',
    'banda_salarial_max' => '=',
    'tipo_onboarding_id' => '=',
  ];

  const sorts = [
    'name' => 'asc',
    'descripcion' => 'asc',
    'status_deleted' => 'asc',
    'area_id' => 'asc',
    'ntrabajadores' => 'asc',
    'banda_salarial_min' => 'asc',
    'banda_salarial_media' => 'asc',
    'banda_salarial_max' => 'asc',
    'tipo_onboarding_id' => 'asc',
  ];

  protected static function boot()
  {
    parent::boot();
    static::saving(function ($model) {
      if (Str::contains($model->name, 'GERENTE')) {
        $model->per_diem_category_id = 1;
      } else {
        $model->per_diem_category_id = 2;
      }
    });
  }

  const POSITION_JEFE_TALLER_ID = 143;
  const POSITION_GERENTE_TALLER_ID = 142;

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper($value);
  }

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper($value);
  }

  public function area()
  {
    return $this->belongsTo(Area::class, 'area_id');
  }

  public function lidership()
  {
    return $this->belongsTo(Position::class, 'cargo_id');
  }

  public function persons()
  {
    return $this->hasMany(Worker::class, 'cargo_id')
      ->where('status_deleted', 1)
      ->where('b_empleado', 1)
      ->where('status_id', 22);
  }

  public function hierarchicalCategory()
  {
    return $this->hasOneThrough(
      HierarchicalCategory::class,
      HierarchicalCategoryDetail::class,
      'position_id',           // Foreign key en la tabla intermedia (HierarchicalCategoryDetail)
      'id',                    // Local key en HierarchicalCategory
      'id',                    // Local key en Position (este modelo)
      'hierarchical_category_id' // Foreign key que conecta con HierarchicalCategory
    );
  }

  public function typeOnboarding()
  {
    return $this->belongsTo(TypeOnboarding::class, 'tipo_onboarding_id');
  }

//    public function perfil()
//    {
//        return $this->belongsTo(PerfilxCargo::class, 'perfil_id');
//    }

//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logAll();
//    }
}
