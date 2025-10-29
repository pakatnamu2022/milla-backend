<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectronicDocumentItem extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_electronic_document_items';

    protected $fillable = [
        'ap_billing_electronic_document_id',
        'unidad_de_medida',
        'codigo',
        'descripcion',
        'cantidad',
        'valor_unitario',
        'precio_unitario',
        'descuento',
        'subtotal',
        'ap_billing_igv_type_id',
        'igv',
        'isc',
        'isc_tipo',
        'total',
        'anticipo_regularizacion',
        'anticipo_documento_serie',
        'anticipo_documento_numero',
        'orden',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'valor_unitario' => 'decimal:10',
        'precio_unitario' => 'decimal:10',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'isc' => 'decimal:2',
        'total' => 'decimal:2',
        'anticipo_regularizacion' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Relaciones
     */
    public function electronicDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'ap_billing_electronic_document_id');
    }

    public function igvType(): BelongsTo
    {
        return $this->belongsTo(IgvType::class, 'ap_billing_igv_type_id');
    }

    /**
     * Scopes
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('orden');
    }

    public function scopeByDocument($query, int $documentId)
    {
        return $query->where('ap_billing_electronic_document_id', $documentId);
    }

    public function scopeGravadas($query)
    {
        return $query->whereHas('igvType', function ($q) {
            $q->where('codigo', '10');
        });
    }

    public function scopeExoneradas($query)
    {
        return $query->whereHas('igvType', function ($q) {
            $q->where('codigo', '20');
        });
    }

    public function scopeInafectas($query)
    {
        return $query->whereHas('igvType', function ($q) {
            $q->where('codigo', '30');
        });
    }

    public function scopeGratuitas($query)
    {
        return $query->whereHas('igvType', function ($q) {
            $q->whereIn('codigo', ['11', '12', '13', '14', '15', '16', '17', '21', '31', '32', '33', '34', '35', '36', '37']);
        });
    }

    /**
     * Accessors
     */
    public function getSubtotalCalculadoAttribute(): float
    {
        return round($this->cantidad * $this->valor_unitario - $this->descuento, 2);
    }

    public function getIgvCalculadoAttribute(): float
    {
        if (!$this->igvType || $this->igvType->codigo !== '10') {
            return 0;
        }

        $porcentajeIgv = $this->electronicDocument->porcentaje_de_igv ?? 18;
        return round($this->subtotal * ($porcentajeIgv / 100), 2);
    }

    public function getTotalCalculadoAttribute(): float
    {
        return round($this->subtotal + $this->igv + $this->isc, 2);
    }

    public function getIsGratuitoAttribute(): bool
    {
        if (!$this->igvType) {
            return false;
        }

        return in_array($this->igvType->codigo, ['11', '12', '13', '14', '15', '16', '17', '21', '31', '32', '33', '34', '35', '36', '37']);
    }

    /**
     * Métodos de negocio
     */
    public function recalculate(): void
    {
        $subtotal = $this->subtotal_calculado;
        $igv = $this->igv_calculado;
        $total = round($subtotal + $igv + $this->isc, 2);

        $this->update([
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
        ]);
    }
}
