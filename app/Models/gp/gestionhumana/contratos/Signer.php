<?php

namespace App\Models\gp\gestionhumana\contratos;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * Firmante con certificado X.509, tabla legacy `rrhh_firmante`. Equivale a
 * Configuraciones/FirmantesController del legacy web_millagp_2 (F5).
 *
 * `file` y `key` son rutas (relativas al disco `local`, es decir
 * storage/app/private) al certificado (.cer) y a la llave privada (.pem/.key),
 * ambos en formato PEM. `password` se guarda cifrada con el cifrado de
 * aplicación (Crypt) — el legacy la guardaba en texto plano.
 */
class Signer extends BaseModel
{
  use Reportable, SoftDeletes;

  protected $table = 'rrhh_firmante';

  protected $fillable = [
    'nombre',
    'file',
    'key',
    'firmaimg',
    'password',
    'fecha_vencimiento',
    'persona_id',
    'sucursal_id',
    'write_id',
    'status_deleted',
  ];

  protected $casts = [
    'fecha_vencimiento' => 'date',
  ];

  protected $hidden = [
    'password',
  ];

  const filters = [
    'search' => ['nombre'],
    'sucursal_id' => '=',
  ];

  const sorts = ['id', 'nombre', 'fecha_vencimiento'];

  protected $reportColumns = [
    'id'                => ['label' => 'ID', 'width' => 8],
    'nombre'            => ['label' => 'NOMBRE', 'width' => 35],
    'sede.abreviatura'  => ['label' => 'SEDE', 'width' => 15],
    'fecha_vencimiento' => ['label' => 'VENCIMIENTO CERTIFICADO', 'width' => 18, 'formatter' => 'date'],
  ];

  protected $reportRelations = ['sede'];

  protected static function booted(): void
  {
    static::addGlobalScope('active', fn(Builder $b) => $b->where('status_deleted', 1));
  }

  public function setPasswordAttribute($value): void
  {
    $this->attributes['password'] = $value === null || $value === '' ? null : Crypt::encryptString($value);
  }

  public function getDecryptedPassword(): string
  {
    return $this->attributes['password'] ? Crypt::decryptString($this->attributes['password']) : '';
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'persona_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sucursal_id');
  }
}
