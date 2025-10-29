<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionType extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_transaction_types';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Códigos SUNAT - Catálogo 51
    const CODIGO_VENTA_INTERNA = '0101';
    const CODIGO_EXPORTACION = '0200';
    const CODIGO_NO_DOMICILIADOS = '0201';
    const CODIGO_VENTA_INTERNA_ANTICIPOS = '0102';
    const CODIGO_VENTA_ITINERANTE = '0103';
    const CODIGO_OPERACIONES_SUJETAS_DETRACCION = '1001';
    const CODIGO_OPERACIONES_SUJETAS_PERCEPCION = '2001';

    /**
     * Relaciones
     */
    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'ap_billing_transaction_type_id');
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

    public function scopeVentaInterna($query)
    {
        return $query->where('codigo', self::CODIGO_VENTA_INTERNA);
    }

    public function scopeExportacion($query)
    {
        return $query->where('codigo', self::CODIGO_EXPORTACION);
    }

    public function scopeConDetraccion($query)
    {
        return $query->where('codigo', self::CODIGO_OPERACIONES_SUJETAS_DETRACCION);
    }

    public function scopeConPercepcion($query)
    {
        return $query->where('codigo', self::CODIGO_OPERACIONES_SUJETAS_PERCEPCION);
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getIsVentaInternaAttribute(): bool
    {
        return $this->codigo === self::CODIGO_VENTA_INTERNA;
    }

    public function getIsExportacionAttribute(): bool
    {
        return $this->codigo === self::CODIGO_EXPORTACION;
    }

    public function getRequiereDetraccionAttribute(): bool
    {
        return $this->codigo === self::CODIGO_OPERACIONES_SUJETAS_DETRACCION;
    }

    public function getRequierePercepcionAttribute(): bool
    {
        return $this->codigo === self::CODIGO_OPERACIONES_SUJETAS_PERCEPCION;
    }
}
