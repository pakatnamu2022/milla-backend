<?php

namespace App\Models\gp\gestionhumana\contratos;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contrato de trabajo, tabla legacy `rrhh_contrato`. Equivale a
 * AdministracionPersonal/ContratoController del legacy.
 *
 * `firmante_id`/`firmante_sec_id`/`convenio`/`lote` se asignan al crear/editar el
 * contrato. El resto de columnas del flujo de firma (solicitar_firma,
 * conformidad_rrhh, confirmacion_firmante, fecha_*, conformidad_lectura,
 * estado_envio_email, observacion_rechazado) se dejan fuera de $fillable a propósito:
 * solo ContractSignatureService debe mutarlas, nunca un update() genérico del usuario.
 */
class Contract extends BaseModel
{
  use Reportable, SoftDeletes;

  protected $table = 'rrhh_contrato';

  protected $fillable = [
    'empleado_id',
    'tipo_contrato_id',
    'template_contrato_id',
    'sede_id',
    'cargo_id',
    'sueldo',
    'fecha_inicio_actividades',
    'fecha_inicio_contrato',
    'fecha_fin_contrato',
    'observacion',
    'grupo_contrato',
    'contrato_principal',
    'convenio',
    'firmante_id',
    'firmante_sec_id',
    'lote',
    'write_id',
    'status_deleted',
  ];

  protected $casts = [
    'sueldo'                   => 'decimal:2',
    'fecha_inicio_actividades' => 'date',
    'fecha_inicio_contrato'    => 'date',
    'fecha_fin_contrato'       => 'date',
  ];

  const filters = [
    'search'           => ['worker.nombre_completo', 'worker.vat'],
    'empleado_id'       => '=',
    'tipo_contrato_id'  => '=',
    'sede_id'           => '=',
    'fecha_inicio_contrato' => 'date_between',
    'fecha_fin_contrato'    => 'date_between',
  ];

  const sorts = ['id', 'fecha_inicio_contrato', 'fecha_fin_contrato'];

  protected $reportColumns = [
    'id'                       => ['label' => 'ID', 'width' => 8],
    'worker.nombre_completo'   => ['label' => 'TRABAJADOR', 'width' => 30],
    'worker.vat'               => ['label' => 'DOCUMENTO', 'width' => 14],
    'contractType.descripcion' => ['label' => 'TIPO CONTRATO', 'width' => 25],
    'sede.abreviatura'         => ['label' => 'SEDE', 'width' => 15],
    'position.name'            => ['label' => 'CARGO', 'width' => 20],
    'sueldo'                   => ['label' => 'SUELDO', 'width' => 12, 'formatter' => 'number'],
    'fecha_inicio_contrato'    => ['label' => 'FECHA INICIO', 'width' => 14, 'formatter' => 'date'],
    'fecha_fin_contrato'       => ['label' => 'FECHA FIN', 'width' => 14, 'formatter' => 'date'],
  ];

  protected $reportRelations = ['worker', 'contractType', 'sede', 'position'];

  protected static function booted(): void
  {
    static::addGlobalScope('active', fn(Builder $b) => $b->where('status_deleted', 1));
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'empleado_id');
  }

  public function contractType(): BelongsTo
  {
    return $this->belongsTo(ContractType::class, 'tipo_contrato_id');
  }

  public function contractTemplate(): BelongsTo
  {
    return $this->belongsTo(ContractTemplate::class, 'template_contrato_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function position(): BelongsTo
  {
    return $this->belongsTo(Position::class, 'cargo_id');
  }

  public function parentContract(): BelongsTo
  {
    return $this->belongsTo(self::class, 'contrato_principal');
  }

  public function signer(): BelongsTo
  {
    return $this->belongsTo(Signer::class, 'firmante_id');
  }

  public function secondarySigner(): BelongsTo
  {
    return $this->belongsTo(Signer::class, 'firmante_sec_id');
  }
}
