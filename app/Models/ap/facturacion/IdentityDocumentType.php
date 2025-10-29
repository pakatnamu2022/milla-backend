<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdentityDocumentType extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_identity_document_types';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'abreviatura',
        'longitud',
        'validacion_regex',
        'activo',
    ];

    protected $casts = [
        'longitud' => 'integer',
        'activo' => 'boolean',
    ];

    // Códigos SUNAT - Catálogo 06
    const CODIGO_DNI = '1';
    const CODIGO_RUC = '6';
    const CODIGO_CARNET_EXTRANJERIA = '4';
    const CODIGO_PASAPORTE = '7';
    const CODIGO_CEDULA_DIPLOMATICA = 'A';
    const CODIGO_DOC_TRIBUTARIO_NO_DOMICILIADO = '0';
    const CODIGO_DOC_IDENTIDAD_PAIS_RESIDENCIA = 'B';

    /**
     * Relaciones
     */
    public function electronicDocuments(): HasMany
    {
        return $this->hasMany(ElectronicDocument::class, 'ap_billing_identity_document_type_id');
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

    public function scopeDni($query)
    {
        return $query->where('codigo', self::CODIGO_DNI);
    }

    public function scopeRuc($query)
    {
        return $query->where('codigo', self::CODIGO_RUC);
    }

    public function scopeCarnetExtranjeria($query)
    {
        return $query->where('codigo', self::CODIGO_CARNET_EXTRANJERIA);
    }

    public function scopePasaporte($query)
    {
        return $query->where('codigo', self::CODIGO_PASAPORTE);
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getIsDniAttribute(): bool
    {
        return $this->codigo === self::CODIGO_DNI;
    }

    public function getIsRucAttribute(): bool
    {
        return $this->codigo === self::CODIGO_RUC;
    }

    public function getIsCarnetExtranjeriaAttribute(): bool
    {
        return $this->codigo === self::CODIGO_CARNET_EXTRANJERIA;
    }

    public function getIsPasaporteAttribute(): bool
    {
        return $this->codigo === self::CODIGO_PASAPORTE;
    }

    /**
     * Métodos de negocio
     */
    public function validarDocumento(string $documento): bool
    {
        // Validar longitud si está definida
        if ($this->longitud && strlen($documento) !== $this->longitud) {
            return false;
        }

        // Validar con regex si está definido
        if ($this->validacion_regex && !preg_match($this->validacion_regex, $documento)) {
            return false;
        }

        // Validaciones específicas
        if ($this->is_ruc) {
            return $this->validarRuc($documento);
        }

        if ($this->is_dni) {
            return $this->validarDni($documento);
        }

        return true;
    }

    private function validarRuc(string $ruc): bool
    {
        if (strlen($ruc) !== 11) {
            return false;
        }

        if (!ctype_digit($ruc)) {
            return false;
        }

        // Validar dígito verificador
        $suma = 0;
        $factor = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 10; $i++) {
            $suma += $ruc[$i] * $factor[$i];
        }

        $resto = $suma % 11;
        $digitoVerificador = 11 - $resto;

        if ($digitoVerificador === 11) {
            $digitoVerificador = 0;
        } elseif ($digitoVerificador === 10) {
            $digitoVerificador = 1;
        }

        return (int) $ruc[10] === $digitoVerificador;
    }

    private function validarDni(string $dni): bool
    {
        if (strlen($dni) !== 8) {
            return false;
        }

        return ctype_digit($dni);
    }

    public function formatearDocumento(string $documento): string
    {
        // Remover caracteres no alfanuméricos
        $documento = preg_replace('/[^a-zA-Z0-9]/', '', $documento);

        // Convertir a mayúsculas
        $documento = strtoupper($documento);

        // Si tiene longitud definida, rellenar con ceros a la izquierda
        if ($this->longitud && strlen($documento) < $this->longitud && ctype_digit($documento)) {
            $documento = str_pad($documento, $this->longitud, '0', STR_PAD_LEFT);
        }

        return $documento;
    }
}
