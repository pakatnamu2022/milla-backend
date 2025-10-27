<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNoteType extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_credit_note_types';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Códigos SUNAT - Catálogo 09
    const CODIGO_ANULACION_OPERACION = '01';
    const CODIGO_ANULACION_ERROR_RUC = '02';
    const CODIGO_CORRECCION_ERROR_DESCRIPCION = '03';
    const CODIGO_DESCUENTO_GLOBAL = '04';
    const CODIGO_DESCUENTO_ITEM = '05';
    const CODIGO_DEVOLUCION_TOTAL = '06';
    const CODIGO_DEVOLUCION_PARCIAL = '07';
    const CODIGO_BONIFICACION = '08';
    const CODIGO_DISMINUCION_VALOR = '09';
    const CODIGO_OTROS_CONCEPTOS = '10';
    const CODIGO_AJUSTES_AFECTACION_IGV = '11';
    const CODIGO_AJUSTES_EXPORTACIONES = '12';

    /**
     * Relaciones
     */
    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'ap_billing_credit_note_type_id');
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    public function scopeByCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    public function scopeAnulaciones($query)
    {
        return $query->whereIn('codigo', [
            self::CODIGO_ANULACION_OPERACION,
            self::CODIGO_ANULACION_ERROR_RUC,
        ]);
    }

    public function scopeDevoluciones($query)
    {
        return $query->whereIn('codigo', [
            self::CODIGO_DEVOLUCION_TOTAL,
            self::CODIGO_DEVOLUCION_PARCIAL,
        ]);
    }

    public function scopeDescuentos($query)
    {
        return $query->whereIn('codigo', [
            self::CODIGO_DESCUENTO_GLOBAL,
            self::CODIGO_DESCUENTO_ITEM,
        ]);
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getIsAnulacionAttribute(): bool
    {
        return in_array($this->codigo, [
            self::CODIGO_ANULACION_OPERACION,
            self::CODIGO_ANULACION_ERROR_RUC,
        ]);
    }

    public function getIsDevolucionAttribute(): bool
    {
        return in_array($this->codigo, [
            self::CODIGO_DEVOLUCION_TOTAL,
            self::CODIGO_DEVOLUCION_PARCIAL,
        ]);
    }

    public function getIsDescuentoAttribute(): bool
    {
        return in_array($this->codigo, [
            self::CODIGO_DESCUENTO_GLOBAL,
            self::CODIGO_DESCUENTO_ITEM,
        ]);
    }

    public function getIsCorreccionAttribute(): bool
    {
        return $this->codigo === self::CODIGO_CORRECCION_ERROR_DESCRIPCION;
    }

    public function getIsBonificacionAttribute(): bool
    {
        return $this->codigo === self::CODIGO_BONIFICACION;
    }

    public function getRequiereDevolucionProductosAttribute(): bool
    {
        return in_array($this->codigo, [
            self::CODIGO_DEVOLUCION_TOTAL,
            self::CODIGO_DEVOLUCION_PARCIAL,
        ]);
    }

    public function getAfectaStockAttribute(): bool
    {
        return $this->requiere_devolucion_productos;
    }

    public function getCategoriaAttribute(): string
    {
        return match(true) {
            $this->is_anulacion => 'Anulación',
            $this->is_devolucion => 'Devolución',
            $this->is_descuento => 'Descuento',
            $this->is_correccion => 'Corrección',
            $this->is_bonificacion => 'Bonificación',
            default => 'Otros',
        };
    }

    public function getCategoriaColorAttribute(): string
    {
        return match(true) {
            $this->is_anulacion => 'danger',
            $this->is_devolucion => 'warning',
            $this->is_descuento => 'info',
            $this->is_correccion => 'primary',
            $this->is_bonificacion => 'success',
            default => 'secondary',
        };
    }
}
