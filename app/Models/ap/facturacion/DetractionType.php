<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetractionType extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_detraction_types';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'porcentaje',
        'activo',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // Códigos SUNAT - Anexo 3
    const CODIGO_AZUCAR = '001';
    const CODIGO_ALCOHOL = '002';
    const CODIGO_RECURSOS_HIDROBIOLOGICOS = '003';
    const CODIGO_MADERA = '004';
    const CODIGO_ARENA_PIEDRA = '005';
    const CODIGO_RESIDUOS_DESPERDICIOS = '006';
    const CODIGO_BIENES_INTERMEDIACION = '007';
    const CODIGO_BIENES_APENDICE_I = '008';
    const CODIGO_ALGODON = '009';
    const CODIGO_MINERALES = '010';
    const CODIGO_ORO = '011';
    const CODIGO_CARNES_DESPOJOS = '012';
    const CODIGO_LECHE = '014';
    const CODIGO_MAIZ_AMARILLO = '015';
    const CODIGO_BIENES_TITULO_I_DL_1126 = '016';
    const CODIGO_EMBARCACIONES_PESQUERAS = '017';
    const CODIGO_ARRENDAMIENTO_BIENES = '019';
    const CODIGO_MANTENIMIENTO_REPARACION = '020';
    const CODIGO_MOVIMIENTO_CARGA = '021';
    const CODIGO_SERVICIOS_EMPRESARIALES = '022';
    const CODIGO_ARRENDAMIENTO_BIENES_MUEBLES = '023';
    const CODIGO_INTERMEDIACION_LABORAL = '030';
    const CODIGO_CONTRATAS_CONSTRUCCION = '031';
    const CODIGO_DEMÁS_SERVICIOS_GRAVADOS = '037';
    const CODIGO_TRANSPORTE_BIENES_CARRETERA = '040';
    const CODIGO_TRANSPORTE_PUBLICO_PASAJEROS = '041';

    /**
     * Relaciones
     */
    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'ap_billing_detraction_type_id');
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

    public function scopeBienes($query)
    {
        return $query->where('codigo', '<=', '018');
    }

    public function scopeServicios($query)
    {
        return $query->where('codigo', '>=', '019');
    }

    public function scopeTransporte($query)
    {
        return $query->whereIn('codigo', [
            self::CODIGO_TRANSPORTE_BIENES_CARRETERA,
            self::CODIGO_TRANSPORTE_PUBLICO_PASAJEROS,
        ]);
    }

    public function scopeConstruccion($query)
    {
        return $query->where('codigo', self::CODIGO_CONTRATAS_CONSTRUCCION);
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getPorcentajeFormateadoAttribute(): string
    {
        return number_format($this->porcentaje, 2) . '%';
    }

    public function getEsBienAttribute(): bool
    {
        return $this->codigo <= '018';
    }

    public function getEsServicioAttribute(): bool
    {
        return $this->codigo >= '019';
    }

    public function getEsTransporteAttribute(): bool
    {
        return in_array($this->codigo, [
            self::CODIGO_TRANSPORTE_BIENES_CARRETERA,
            self::CODIGO_TRANSPORTE_PUBLICO_PASAJEROS,
        ]);
    }

    public function getEsConstruccionAttribute(): bool
    {
        return $this->codigo === self::CODIGO_CONTRATAS_CONSTRUCCION;
    }

    public function getCategoriaAttribute(): string
    {
        if ($this->es_bien) {
            return 'Bienes';
        }

        if ($this->es_transporte) {
            return 'Transporte';
        }

        if ($this->es_construccion) {
            return 'Construcción';
        }

        return 'Servicios';
    }

    public function getCategoriaColorAttribute(): string
    {
        return match($this->categoria) {
            'Bienes' => 'primary',
            'Transporte' => 'info',
            'Construcción' => 'warning',
            'Servicios' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Métodos de negocio
     */
    public function calcularDetraccion(float $baseImponible): float
    {
        return round($baseImponible * ($this->porcentaje / 100), 2);
    }

    public function aplicaDetraccion(float $montoOperacion, Currency $moneda = null): bool
    {
        // Montos mínimos según tipo
        $montoMinimo = $this->obtenerMontoMinimo();

        // Si hay moneda y no es PEN, convertir
        if ($moneda && !$moneda->is_pen) {
            // Aquí se debería convertir a PEN usando tipo de cambio
            // Por simplicidad, asumimos que el monto ya está en la moneda correcta
        }

        return $montoOperacion >= $montoMinimo;
    }

    public function obtenerMontoMinimo(): float
    {
        // Según normativa SUNAT
        if ($this->es_bien) {
            return 700.00; // S/ 700 para bienes
        }

        if ($this->es_transporte) {
            return 400.00; // S/ 400 para transporte
        }

        return 700.00; // S/ 700 para servicios en general
    }

    /**
     * Validar si requiere constancia de depósito
     */
    public function requiereConstanciaDeposito(float $montoDetraccion): bool
    {
        // Según normas SUNAT, siempre que se aplique detracción
        return $montoDetraccion > 0;
    }
}
