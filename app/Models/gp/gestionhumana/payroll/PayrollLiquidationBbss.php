<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\GpMasters;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollLiquidationBbss extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_liquidation_bbss';

  // Códigos del catálogo GpMasters (type=LIQUIDATION_BBSS), ver
  // Database\Seeders\gp\gestionhumana\payroll\PayrollLiquidationBbssTypeSeeder.
  const string TYPE_CTS_TRUNCADA = 'CTS_TRUNCADA';
  const string TYPE_GRATIFICACION_TRUNCADA = 'GRATIFICACION_TRUNCADA';
  const string TYPE_BONIFICACION_EXTRAORDINARIA = 'BONIFICACION_EXTRAORDINARIA';
  const string TYPE_VACACIONES_TRUNCADAS = 'VACACIONES_TRUNCADAS';
  const string TYPE_AGUINALDO = 'AGUINALDO';
  const string TYPE_GRATIFICACION_NAVIDAD = 'GRATIFICACION_NAVIDAD';
  const string TYPE_BONIF_EXTRAORD_NAVIDAD = 'BONIF_EXTRAORD_NAVIDAD';

  /**
   * Mapa code => id de GpMasters para los tipos de este catálogo.
   * Se resuelve por código (no por id fijo) porque los ids dependen del seeder.
   */
  public static function typeIdsByCode(): array
  {
    return GpMasters::where('type', 'LIQUIDATION_BBSS')
      ->pluck('id', 'code')
      ->toArray();
  }

  protected $fillable = [
    'worker_id',
    'period_id',
    'amount',
    'type_id',
    'status',
  ];

  protected $casts = [
    'amount' => 'decimal:2',
    'status' => 'integer',
  ];

  const filters = [
    'search' => [],
    'worker_id' => '=',
    'period_id' => '=',
    'type_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'worker_id',
    'period_id',
    'type_id',
    'amount',
    'created_at',
  ];

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function period(): BelongsTo
  {
    return $this->belongsTo(PayrollPeriod::class, 'period_id');
  }

  public function type(): BelongsTo
  {
    return $this->belongsTo(GpMasters::class, 'type_id');
  }
}
