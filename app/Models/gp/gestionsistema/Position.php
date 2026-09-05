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
    'per_diem_category_id',
    'no_attendance_required',
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

  const array ASESOR_SERVICIO_PV_IDS = [63, 73, 89, 131];
  const array AUXILIAR_SERVICIO_PV_IDS = [67, 95, 137, 370];
  const array ASESOR_REPUESTOS_PV_IDS = [62, 72, 88, 130, 318, 349];
  const array JEFE_TALLER_PV_IDS = [69, 99, 143, 246];
  const array JEFE_REPUESTO_PV_IDS = [344];
  const array COORDINADOR_TALLER_IDS = [68, 78, 98, 140];
  const array ASISTENTE_PV_IDS = [90, 133];
  const array COORDINADOR_PV_IDS = [141];
  const array JEFE_ALMACEN_PV_IDS = [56, 86, 248];
  const array ASISTENTE_ALMACEN_PV_IDS = [57, 87, 129, 233, 251, 309];
  const array CODIFICADOR_PV_IDS = [77, 97, 139];
  const array GERENTE_PV_IDS = [142];
  const array ZONAL_ACCOUNTING_ANALYST = [301, 302];
  const array HEAD_ACCOUNTING = [44, 288];
  const array TICS_ANALYST = [273];
  const array JEFE_TICS = [345];

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
