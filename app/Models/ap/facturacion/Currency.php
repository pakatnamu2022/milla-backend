<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_currencies';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'decimales',
        'descripcion',
        'es_moneda_nacional',
        'activo',
    ];

    protected $casts = [
        'decimales' => 'integer',
        'es_moneda_nacional' => 'boolean',
        'activo' => 'boolean',
    ];

    // Códigos ISO 4217
    const CODIGO_PEN = 'PEN';
    const CODIGO_USD = 'USD';
    const CODIGO_EUR = 'EUR';

    /**
     * Relaciones
     */
    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'ap_billing_currency_id');
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

    public function scopeMonedaNacional($query)
    {
        return $query->where('es_moneda_nacional', true);
    }

    public function scopeMonedaExtranjera($query)
    {
        return $query->where('es_moneda_nacional', false);
    }

    public function scopePEN($query)
    {
        return $query->where('codigo', self::CODIGO_PEN);
    }

    public function scopeUSD($query)
    {
        return $query->where('codigo', self::CODIGO_USD);
    }

    public function scopeEUR($query)
    {
        return $query->where('codigo', self::CODIGO_EUR);
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getIsPenAttribute(): bool
    {
        return $this->codigo === self::CODIGO_PEN;
    }

    public function getIsUsdAttribute(): bool
    {
        return $this->codigo === self::CODIGO_USD;
    }

    public function getIsEurAttribute(): bool
    {
        return $this->codigo === self::CODIGO_EUR;
    }

    public function getRequiereTipoCambioAttribute(): bool
    {
        return !$this->es_moneda_nacional;
    }

    /**
     * Métodos de negocio
     */
    public function formatear(float $monto, bool $incluirSimbolo = true): string
    {
        $montoFormateado = number_format($monto, $this->decimales, '.', ',');

        if ($incluirSimbolo) {
            return "{$this->simbolo} {$montoFormateado}";
        }

        return $montoFormateado;
    }

    public function redondear(float $monto): float
    {
        return round($monto, $this->decimales);
    }

    public function convertir(float $monto, float $tipoCambio, Currency $monedaDestino): float
    {
        // Si es la misma moneda, no convertir
        if ($this->codigo === $monedaDestino->codigo) {
            return $monto;
        }

        // Convertir primero a moneda nacional si no lo es
        $montoEnMonedaNacional = $this->es_moneda_nacional
            ? $monto
            : $monto * $tipoCambio;

        // Si la moneda destino es nacional, retornar
        if ($monedaDestino->es_moneda_nacional) {
            return $monedaDestino->redondear($montoEnMonedaNacional);
        }

        // Convertir de moneda nacional a moneda destino
        return $monedaDestino->redondear($montoEnMonedaNacional / $tipoCambio);
    }

    /**
     * Obtener el tipo de cambio actual desde una API o BD
     */
    public static function obtenerTipoCambio(string $monedaOrigen, string $monedaDestino, ?\DateTime $fecha = null): ?float
    {
        // Implementar lógica para obtener tipo de cambio
        // Puede ser desde una tabla de tipos de cambio o desde una API externa

        // Por ahora retornar null, debe implementarse según necesidad
        return null;
    }

    /**
     * Validar si un monto es válido para esta moneda
     */
    public function validarMonto(float $monto): bool
    {
        // Verificar que el monto tenga los decimales correctos
        $decimalesMonto = strlen(substr(strrchr((string) $monto, '.'), 1));
        return $decimalesMonto <= $this->decimales;
    }
}
