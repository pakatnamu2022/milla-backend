<?php

namespace App\Models\ap\comercial;

use App\Http\Traits\Reportable;
use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    'reason_discarding_id',
    'created_at',
    'updated_at',
  ];

  const filters = [
    'search' => ['full_name', 'worker.nombre_completo', 'sede.abreviatura', 'vehicleBrand.name', 'num_doc', 'email', 'phone', 'campaign'],
    'sede_id' => '=',
    'vehicle_brand_id' => '=',
    'document_type_id' => '=',
    'created_at' => 'date_between',
    'type' => '=',
    'income_sector_id' => '=',
    'area_id' => '=',
    'worker_id' => '=',
    'status_num_doc' => '=',
    'use' => '=',
    'registration_date' => 'date_between',
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
    'created_at'
  ];

  public function getClientIdAttribute(): ?int
  {
    $client = BusinessPartners::where('num_doc', $this->num_doc)->first();
    return $client ? $client->id : null;
  }

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
    return $this->belongsTo(ApMasters::class, 'document_type_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function area(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'area_id');
  }

  public function incomeSector(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'income_sector_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function opportunity(): HasOne
  {
    return $this->hasOne(Opportunity::class, 'lead_id');
  }

  public function reasonDiscarding(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'reason_discarding_id');
  }

  // ← CONFIGURACIÓN DEL REPORTE CON FORMATO SOLICITADO
  protected $reportColumns = [
    'registration_date' => [
      'label' => 'Fecha de Registro',
      'formatter' => 'date:d/m/Y',
      'width' => 20,
    ],
    'sede.abreviatura' => [
      'label' => 'Sede',
      'formatter' => null,
      'width' => 20
    ],
    'sede.district.name' => [
      'label' => 'Ciudad',
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
    'status_num_doc' => [
      'label' => 'Estado Validación',
      'formatter' => null,
      'width' => 20,
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
    'attention_status' => [
      'label' => 'Estado de Atención',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getAttentionStatusAttribute'
    ],
    'opportunity_status_name' => [
      'label' => 'Estado',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getOpportunityStatusNameAttribute'
    ],
    'attention_description' => [
      'label' => 'Descripción de la Atención / Descarte',
      'formatter' => null,
      'width' => 40,
      'accessor' => 'getAttentionDescriptionAttribute'
    ],
    'created_at' => [
      'label' => 'Fecha y Hora de Registro',
      'formatter' => 'date:d/m/Y H:i',
      'width' => 25,
    ],
    'attention_updated_at' => [
      'label' => 'Fecha y Hora de Atención',
      'formatter' => 'date:d/m/Y H:i',
      'width' => 25,
      'accessor' => 'getAttentionUpdatedAtAttribute'
    ],
    'campaign' => [
      'label' => 'Campaña',
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
    'opportunity.opportunityStatus',
    'opportunity.actions',
    'reasonDiscarding',
  ];

  public function getAdvisorFullNameAttribute()
  {
    $nombreCompleto = $this->worker ? $this->worker->nombre_completo : 'SIN ASESOR';
    return $nombreCompleto;
  }

  public function getAttentionStatusAttribute()
  {
    switch ($this->use) {
      case self::CREATED:
        return 'NO ATENDIDO';
      case self::USED:
        return 'ATENDIDO';
      case self::DISCARTED:
        return 'DESCARTADO';
      default:
        return 'NO ATENDIDO';
    }
  }

  public function getOpportunityStatusNameAttribute()
  {
    if (!$this->opportunity || !$this->opportunity->opportunityStatus) {
      return 'SIN OPORTUNIDAD';
    }
    return $this->opportunity->opportunityStatus->description;
  }

  public function getAttentionDescriptionAttribute()
  {
    // Si el estado es DESCARTADO, mostrar el motivo de descarte
    if ($this->use == self::DISCARTED) {
      return $this->reasonDiscarding ? $this->reasonDiscarding->description : 'DESCARTADO - Sin motivo especificado';
    }

    // Si está atendido, mostrar la última descripción de las acciones de la oportunidad
    if (!$this->opportunity || !$this->opportunity->actions || $this->opportunity->actions->isEmpty()) {
      return null;
    }

    // Retornar la última descripción
    $lastAction = $this->opportunity->actions->sortByDesc('datetime')->first();
    return $lastAction ? $lastAction->description : null;
  }

  public function getAttentionUpdatedAtAttribute()
  {
    // Si use = 0 (NO ATENDIDO), no mostrar la fecha de cierre
    if ($this->use == self::CREATED) {
      return null;
    }
    return $this->updated_at;
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
