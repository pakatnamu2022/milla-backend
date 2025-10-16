<?php

namespace App\Models\ap\comercial;

use App\Http\Traits\Reportable;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PotentialBuyers extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'potential_buyers';

  protected $fillable = [
    'registration_date',
    'model',
    'version',
    'num_doc',
    'full_name',
    'phone',
    'email',
    'campaign',
    'worker_id',
    'sede_id',
    'vehicle_brand_id',
    'document_type_id',
    'type',
    'income_sector_id',
    'area_id',
    'status_num_doc',
    'use',
    'comment',
    'user_id',
    'created_at',
    'updated_at',
  ];

  const filters = [
    'search' => ['full_name', 'worker.nombre_completo', 'sede.abreviatura', 'vehicleBrand.name', 'num_doc', 'email', 'phone', 'campaign'],
    'sede_id' => '=',
    'vehicle_brand_id' => '=',
    'document_type_id' => '=',
    'registration_date' => 'between',
    'type' => '=',
    'income_sector_id' => '=',
    'area_id' => '=',
    'worker_id' => '=',
    'status_num_doc' => '=',
    'use' => '=',
  ];

  const CREATED = 0;
  const USED = 1;
  const DISCARTED = 2;

  const sorts = [
    'registration_date',
    'full_name',
    'num_doc',
    'email',
    'phone',
    'campaign',
    'sede_id',
    'vehicle_brand_id',
  ];

  public function setModelAttribute($value): void
  {
    $this->attributes['model'] = Str::upper($value);
  }

  public function setVersionAttribute($value): void
  {
    $this->attributes['version'] = Str::upper($value);
  }

  public function setFullNameAttribute($value): void
  {
    $this->attributes['full_name'] = Str::upper($value);
  }

  public function setCampaignAttribute($value): void
  {
    $this->attributes['campaign'] = Str::upper($value);
  }

  public function setTypeAttribute($value): void
  {
    $this->attributes['type'] = Str::upper($value);
  }

  public function vehicleBrand(): BelongsTo
  {
    return $this->belongsTo(ApVehicleBrand::class, 'vehicle_brand_id');
  }

  public function documentType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'document_type_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function area(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'area_id');
  }

  public function incomeSector(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'income_sector_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'user_id');
  }

  // ← CONFIGURACIÓN DEL REPORTE CON FORMATO SOLICITADO
  protected $reportColumns = [
    'registration_date' => [
      'label' => 'Fecha de registro',
      'formatter' => 'date:d/m/Y',
      'width' => 20,
    ],
    'status_num_doc' => [
      'label' => 'Estado Validación',
      'formatter' => null,
      'width' => 20,
    ],
    'sede.abreviatura' => [
      'label' => 'Sede',
      'formatter' => null,
      'width' => 20
    ],
    'vehicleBrand.name' => [
      'label' => 'Marca Vehículo',
      'formatter' => null,
      'width' => 20
    ],
    'asesor' => [
      'label' => 'Asesor',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getAdvisorFullNameAttribute'
    ],
    'sede.district.name' => [
      'label' => 'Distrito Sede',
      'formatter' => null,
      'width' => 20
    ],
    'model' => [
      'label' => 'Modelo',
      'formatter' => null,
      'width' => 20
    ],
    'version' => [
      'label' => 'Versión',
      'formatter' => null,
      'width' => 20
    ],
    'documentType.description' => [
      'label' => 'Tipo Documento',
      'formatter' => null,
      'width' => 20
    ],
    'num_doc' => [
      'label' => 'N° Documento',
      'formatter' => null,
      'width' => 20
    ],
    'full_name' => [
      'label' => 'Nombre Completo',
      'formatter' => null,
      'width' => 30
    ],
    'email' => [
      'label' => 'Email',
      'formatter' => null,
      'width' => 30
    ],
    'phone' => [
      'label' => 'Teléfono',
      'formatter' => null,
      'width' => 20
    ],
  ];

  protected $reportRelations = [
    'sede',
    'vehicleBrand',
    'documentType',
    'worker',
    'sede.district',
  ];

  public function getAdvisorFullNameAttribute()
  {
    $nombreCompleto = $this->worker ? $this->worker->nombre_completo : 'SIN ASESOR';
    return $nombreCompleto;
  }

  /**
   * Filtrar columnas de reporte según contexto (tipo)
   * Si type = 'VISITA', ocultar 'model' y 'version'
   * Si type = 'LEADS', mostrar todas las columnas
   */
  public function filterReportColumns($columns, $context = [])
  {
    $type = $context['type'] ?? null;

    // Si es tipo VISITA, remover las columnas de modelo y versión
    if ($type === 'VISITA') {
      unset($columns['model']);
      unset($columns['version']);
    }

    return $columns;
  }
}
