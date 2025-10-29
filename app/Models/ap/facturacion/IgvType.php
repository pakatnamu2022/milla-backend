<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IgvType extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_igv_types';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo_afectacion',
        'es_oneroso',
        'es_gratuito',
        'activo',
    ];

    protected $casts = [
        'es_oneroso' => 'boolean',
        'es_gratuito' => 'boolean',
        'activo' => 'boolean',
    ];

    // Códigos SUNAT - Catálogo 07
    const CODIGO_GRAVADO = '10';
    const CODIGO_GRAVADO_RETIRO_PREMIO = '11';
    const CODIGO_GRAVADO_RETIRO_DONACION = '12';
    const CODIGO_GRAVADO_RETIRO = '13';
    const CODIGO_GRAVADO_RETIRO_PUBLICIDAD = '14';
    const CODIGO_GRAVADO_BONIFICACIONES = '15';
    const CODIGO_GRAVADO_RETIRO_ENTREGA_TRABAJADORES = '16';
    const CODIGO_GRAVADO_IVAP = '17';
    const CODIGO_EXONERADO = '20';
    const CODIGO_EXONERADO_TRANSFERENCIA_GRATUITA = '21';
    const CODIGO_INAFECTO = '30';
    const CODIGO_INAFECTO_RETIRO_BONIFICACION = '31';
    const CODIGO_INAFECTO_RETIRO = '32';
    const CODIGO_INAFECTO_RETIRO_MUESTRAS = '33';
    const CODIGO_INAFECTO_CONVENIOS = '34';
    const CODIGO_INAFECTO_RETIRO_PREMIO = '35';
    const CODIGO_INAFECTO_RETIRO_PUBLICIDAD = '36';
    const CODIGO_INAFECTO_TRANSFERENCIA_GRATUITA = '37';
    const CODIGO_EXPORTACION = '40';

    // Tipos de afectación
    const TIPO_GRAVADO = 'gravado';
    const TIPO_EXONERADO = 'exonerado';
    const TIPO_INAFECTO = 'inafecto';
    const TIPO_EXPORTACION = 'exportacion';

    /**
     * Relaciones
     */
    public function documentItems(): HasMany
    {
        return $this->hasMany(ElectronicDocumentItem::class, 'ap_billing_igv_type_id');
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

    public function scopeGravados($query)
    {
        return $query->where('tipo_afectacion', self::TIPO_GRAVADO);
    }

    public function scopeExonerados($query)
    {
        return $query->where('tipo_afectacion', self::TIPO_EXONERADO);
    }

    public function scopeInafectos($query)
    {
        return $query->where('tipo_afectacion', self::TIPO_INAFECTO);
    }

    public function scopeOnerosos($query)
    {
        return $query->where('es_oneroso', true);
    }

    public function scopeGratuitos($query)
    {
        return $query->where('es_gratuito', true);
    }

    public function scopeExportacion($query)
    {
        return $query->where('tipo_afectacion', self::TIPO_EXPORTACION);
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getIsGravadoAttribute(): bool
    {
        return $this->tipo_afectacion === self::TIPO_GRAVADO;
    }

    public function getIsExoneradoAttribute(): bool
    {
        return $this->tipo_afectacion === self::TIPO_EXONERADO;
    }

    public function getIsInafectoAttribute(): bool
    {
        return $this->tipo_afectacion === self::TIPO_INAFECTO;
    }

    public function getIsExportacionAttribute(): bool
    {
        return $this->tipo_afectacion === self::TIPO_EXPORTACION;
    }

    public function getAplicaIgvAttribute(): bool
    {
        return $this->tipo_afectacion === self::TIPO_GRAVADO && $this->es_oneroso;
    }

    public function getTipoAfectacionLabelAttribute(): string
    {
        return match($this->tipo_afectacion) {
            self::TIPO_GRAVADO => 'Gravado',
            self::TIPO_EXONERADO => 'Exonerado',
            self::TIPO_INAFECTO => 'Inafecto',
            self::TIPO_EXPORTACION => 'Exportación',
            default => 'Desconocido',
        };
    }

    public function getTipoAfectacionColorAttribute(): string
    {
        return match($this->tipo_afectacion) {
            self::TIPO_GRAVADO => 'success',
            self::TIPO_EXONERADO => 'info',
            self::TIPO_INAFECTO => 'warning',
            self::TIPO_EXPORTACION => 'primary',
            default => 'secondary',
        };
    }

    /**
     * Métodos de negocio
     */
    public function calcularIgv(float $baseImponible, float $porcentajeIgv = 18): float
    {
        if (!$this->aplica_igv) {
            return 0;
        }

        return round($baseImponible * ($porcentajeIgv / 100), 2);
    }

    public function calcularTotal(float $baseImponible, float $porcentajeIgv = 18): float
    {
        if ($this->es_gratuito) {
            return 0;
        }

        $igv = $this->calcularIgv($baseImponible, $porcentajeIgv);
        return round($baseImponible + $igv, 2);
    }
}
