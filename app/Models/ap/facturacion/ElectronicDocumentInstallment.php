<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectronicDocumentInstallment extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_billing_electronic_document_installments';

  protected $fillable = [
    'ap_billing_electronic_document_id',
    'cuota',
    'fecha_de_pago',
    'importe',
  ];

  protected $casts = [
    'fecha_de_pago' => 'date',
    'importe' => 'decimal:2',
  ];

  /**
   * Relaciones
   */
  public function electronicDocument(): BelongsTo
  {
    return $this->belongsTo(ElectronicDocument::class, 'ap_billing_electronic_document_id');
  }

  /**
   * Scopes
   */
  public function scopeByDocument($query, int $documentId)
  {
    return $query->where('ap_billing_electronic_document_id', $documentId);
  }

  public function scopeOrdered($query)
  {
    return $query->orderBy('fecha_de_pago');
  }

  public function scopePendientes($query)
  {
    return $query->where('fecha_de_pago', '>=', now());
  }

  public function scopeVencidas($query)
  {
    return $query->where('fecha_de_pago', '<', now());
  }

  public function scopeByCuota($query, string $cuota)
  {
    return $query->where('cuota', $cuota);
  }

  /**
   * Accessors
   */
  public function getIsPendienteAttribute(): bool
  {
    return $this->fecha_de_pago >= now();
  }

  public function getIsVencidaAttribute(): bool
  {
    return $this->fecha_de_pago < now();
  }

  public function getDiasParaVencimientoAttribute(): int
  {
    return now()->diffInDays($this->fecha_de_pago, false);
  }

  public function getFormattedCuotaAttribute(): string
  {
    return "Cuota {$this->cuota}: " . number_format($this->importe, 2) . " - " . $this->fecha_de_pago->format('d/m/Y');
  }

  /**
   * Métodos de negocio
   */
  public static function createFromArray(int $documentId, array $installments): void
  {
    foreach ($installments as $installment) {
      self::create([
        'ap_billing_electronic_document_id' => $documentId,
        'cuota' => $installment['cuota'] ?? null,
        'fecha_de_pago' => $installment['fecha_de_pago'] ?? null,
        'importe' => $installment['importe'] ?? 0,
      ]);
    }
  }

  public static function generateInstallments(int $documentId, float $total, int $numeroCuotas, string $fechaInicio): void
  {
    $importePorCuota = round($total / $numeroCuotas, 2);
    $fecha = \Carbon\Carbon::parse($fechaInicio);

    for ($i = 1; $i <= $numeroCuotas; $i++) {
      $importe = $i === $numeroCuotas
        ? round($total - ($importePorCuota * ($numeroCuotas - 1)), 2)
        : $importePorCuota;

      self::create([
        'ap_billing_electronic_document_id' => $documentId,
        'cuota' => "Cuota {$i}",
        'fecha_de_pago' => $fecha->copy()->addMonths($i - 1),
        'importe' => $importe,
      ]);
    }
  }
}
