<?php

namespace App\Models\ap;

use App\Models\ap\comercial\Opportunity;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApMasters extends Model
{
  use SoftDeletes;

  protected $table = 'ap_masters'; // ApMasters

  protected $fillable = [
    'id',
    'code',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'type' => 'in_or_equal',
    'status' => '=',
    'open_opportunity_status' => 'accessor_bool',
  ];

  const sorts = [
    'code',
    'description',
    'status',
    'type',
  ];

  //  OPERATION TYPE
  const int TIPO_OPERACION_COMERCIAL = 794;
  const int TIPO_OPERACION_POSTVENTA = 804;
  const int TYPE_PERSON_NATURAL_ID = 704; // Persona Natural
  const int TYPE_PERSON_JURIDICA_ID = 705; // Persona Juridica

  // CLASS TYPE (para clasificación de marcas y clases de artículos)
  // Estos valores se obtienen dinámicamente de la BD, aquí solo como referencia
  // para obtener los IDs reales usar: ApMasters::ofType('CLASS_TYPE')->where('code', '0')->first()->id
  const string CLASS_TYPE_VEHICLE_CODE = '0';
  const string CLASS_TYPE_CAMION_CODE = '1';

  // ESTADOS DE ORDEN DE TRABAJO
  const int OPENING_WORK_ORDER_ID = 884;
  const int RECEIVED_WORK_ORDER_ID = 889;
  const int AT_WORK_WORK_ORDER_ID = 890;
  const int FINISHED_WORK_ORDER_ID = 891;
  const int CLOSED_WORK_ORDER_ID = 892;
  const int CANCELED_WORK_ORDER_ID = 893;

  /**
   * AREAS
   */
  const int AREA_COMERCIAL = 826;
  const int AREA_POSVENTA = 825;
  const int AREA_TALLER = 881;
  const int AREA_MESON = 882;
  const array AREAS_POSVENTA = [self::AREA_POSVENTA, self::AREA_TALLER, self::AREA_MESON];

  const array ALL_AREAS = [
    self::AREA_COMERCIAL,
    self::AREA_POSVENTA,
    self::AREA_TALLER,
    self::AREA_MESON,
  ];

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }

  public function scopeOfType($query, string $type)
  {
    return $query->where('type', strtoupper($type));
  }

  public function commercialManagers()
  {
    return $this->belongsToMany(Worker::class, 'ap_commercial_manager_brand_group', 'brand_group_id', 'commercial_manager_id')
      ->withTimestamps();
  }

  public function sedes()
  {
    return $this->hasMany(Sede::class, 'shop_id');
  }

  public function getOpenOpportunityStatusAttribute()
  {
    return in_array($this->code, Opportunity::OPEN_STATUS_CODES);
  }
}
