<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionhumana\personal\Worker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkerSignature extends Model
{
  use softDeletes;

  protected $table = 'worker_signature';

  protected $fillable = [
    'signature_url',
    'worker_id',
    'company_id',
  ];

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'company_id');
  }
}
