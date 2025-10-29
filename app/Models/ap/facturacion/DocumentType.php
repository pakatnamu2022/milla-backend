<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_document_types';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'prefijo_serie',
        'longitud_serie',
        'longitud_numero',
        'requiere_documento_referencia',
        'es_nota_modificatoria',
        'activo',
    ];

    protected $casts = [
        'longitud_serie' => 'integer',
        'longitud_numero' => 'integer',
        'requiere_documento_referencia' => 'boolean',
        'es_nota_modificatoria' => 'boolean',
        'activo' => 'boolean',
    ];

    // Códigos SUNAT
    const CODIGO_FACTURA = '01';
    const CODIGO_BOLETA = '03';
    const CODIGO_NOTA_CREDITO = '07';
    const CODIGO_NOTA_DEBITO = '08';
    const CODIGO_GUIA_REMISION = '09';
    const CODIGO_RECIBO_HONORARIOS = '12';

    /**
     * Relaciones
     */
    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'ap_billing_document_type_id');
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

    public function scopeFacturas($query)
    {
        return $query->where('codigo', self::CODIGO_FACTURA);
    }

    public function scopeBoletas($query)
    {
        return $query->where('codigo', self::CODIGO_BOLETA);
    }

    public function scopeNotasCredito($query)
    {
        return $query->where('codigo', self::CODIGO_NOTA_CREDITO);
    }

    public function scopeNotasDebito($query)
    {
        return $query->where('codigo', self::CODIGO_NOTA_DEBITO);
    }

    public function scopeNotasModificatorias($query)
    {
        return $query->where('es_nota_modificatoria', true);
    }

    public function scopeComprobantes($query)
    {
        return $query->whereIn('codigo', [self::CODIGO_FACTURA, self::CODIGO_BOLETA]);
    }

    /**
     * Accessors
     */
    public function getIsFacturaAttribute(): bool
    {
        return $this->codigo === self::CODIGO_FACTURA;
    }

    public function getIsBoletaAttribute(): bool
    {
        return $this->codigo === self::CODIGO_BOLETA;
    }

    public function getIsNotaCreditoAttribute(): bool
    {
        return $this->codigo === self::CODIGO_NOTA_CREDITO;
    }

    public function getIsNotaDebitoAttribute(): bool
    {
        return $this->codigo === self::CODIGO_NOTA_DEBITO;
    }

    public function getIsComprobanteAttribute(): bool
    {
        return in_array($this->codigo, [self::CODIGO_FACTURA, self::CODIGO_BOLETA]);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    /**
     * Métodos de negocio
     */
    public function validarSerie(string $serie): bool
    {
        if (strlen($serie) !== $this->longitud_serie) {
            return false;
        }

        if ($this->prefijo_serie && !str_starts_with($serie, $this->prefijo_serie)) {
            return false;
        }

        return true;
    }

    public function validarNumero(int $numero): bool
    {
        $digitosNumero = strlen((string) $numero);
        return $digitosNumero <= $this->longitud_numero;
    }

    public function formatearSerie(string $serie): string
    {
        return strtoupper(str_pad($serie, $this->longitud_serie, '0', STR_PAD_LEFT));
    }

    public function formatearNumero(int $numero): string
    {
        return str_pad($numero, $this->longitud_numero, '0', STR_PAD_LEFT);
    }

    public function generarSerieDefault(): string
    {
        if (!$this->prefijo_serie) {
            return str_pad('1', $this->longitud_serie, '0', STR_PAD_LEFT);
        }

        $resto = $this->longitud_serie - strlen($this->prefijo_serie);
        return $this->prefijo_serie . str_pad('1', $resto, '0', STR_PAD_LEFT);
    }
}
