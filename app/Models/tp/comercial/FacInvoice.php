<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacInvoice extends BaseModel
{
  protected $table = 'fac_invoice';

  public function tipoComprobante(): BelongsTo
  {
    return $this->belongsTo(TipoComprobante::class, 'tipo_comprobante_id');
  }

  public function getDocumentoIdAttribute(): string
  {
    $prefix = $this->tipoComprobante->desc_gp;
    $serie = trim((string)$this->serie);
    $numero = str_pad(trim((string)$this->numero), 8, '0', STR_PAD_LEFT);

    return "{$prefix} {$serie}-{$numero}";
  }
}
