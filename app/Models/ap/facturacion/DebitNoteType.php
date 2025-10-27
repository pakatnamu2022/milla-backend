<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNoteType extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_debit_note_types';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Códigos SUNAT - Catálogo 10
    const CODIGO_INTERESES_MORA = '01';
    const CODIGO_AUMENTO_VALOR = '02';
    const CODIGO_PENALIDADES = '03';
    const CODIGO_OTROS_CONCEPTOS = '10';

    /**
     * Relaciones
     */
    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'ap_billing_debit_note_type_id');
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

    public function scopeInteresesMora($query)
    {
        return $query->where('codigo', self::CODIGO_INTERESES_MORA);
    }

    public function scopeAumentoValor($query)
    {
        return $query->where('codigo', self::CODIGO_AUMENTO_VALOR);
    }

    public function scopePenalidades($query)
    {
        return $query->where('codigo', self::CODIGO_PENALIDADES);
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getIsInteresesMoraAttribute(): bool
    {
        return $this->codigo === self::CODIGO_INTERESES_MORA;
    }

    public function getIsAumentoValorAttribute(): bool
    {
        return $this->codigo === self::CODIGO_AUMENTO_VALOR;
    }

    public function getIsPenalidadesAttribute(): bool
    {
        return $this->codigo === self::CODIGO_PENALIDADES;
    }

    public function getIsOtrosConceptosAttribute(): bool
    {
        return $this->codigo === self::CODIGO_OTROS_CONCEPTOS;
    }

    public function getRequiereCalculoInteresAttribute(): bool
    {
        return $this->codigo === self::CODIGO_INTERESES_MORA;
    }

    public function getCategoriaAttribute(): string
    {
        return match($this->codigo) {
            self::CODIGO_INTERESES_MORA => 'Intereses por Mora',
            self::CODIGO_AUMENTO_VALOR => 'Aumento de Valor',
            self::CODIGO_PENALIDADES => 'Penalidades',
            self::CODIGO_OTROS_CONCEPTOS => 'Otros Conceptos',
            default => 'Desconocido',
        };
    }

    public function getCategoriaColorAttribute(): string
    {
        return match($this->codigo) {
            self::CODIGO_INTERESES_MORA => 'warning',
            self::CODIGO_AUMENTO_VALOR => 'info',
            self::CODIGO_PENALIDADES => 'danger',
            self::CODIGO_OTROS_CONCEPTOS => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Métodos de negocio
     */
    public function calcularInteresMora(float $montoBase, float $tasaInteresDiario, int $diasMora): float
    {
        if (!$this->requiere_calculo_interes) {
            return 0;
        }

        return round($montoBase * ($tasaInteresDiario / 100) * $diasMora, 2);
    }
}
