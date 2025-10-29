<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectronicDocumentGuide extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_electronic_document_guides';

    protected $fillable = [
        'ap_billing_electronic_document_id',
        'guia_tipo',
        'guia_serie_numero',
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

    public function scopeByType($query, string $type)
    {
        return $query->where('guia_tipo', $type);
    }

    public function scopeBySerieNumero($query, string $serieNumero)
    {
        return $query->where('guia_serie_numero', $serieNumero);
    }

    /**
     * Accessors
     */
    public function getGuiaFormatAttribute(): string
    {
        return "{$this->guia_tipo} - {$this->guia_serie_numero}";
    }

    /**
     * Métodos de negocio
     */
    public static function createFromArray(int $documentId, array $guides): void
    {
        foreach ($guides as $guide) {
            self::create([
                'ap_billing_electronic_document_id' => $documentId,
                'guia_tipo' => $guide['guia_tipo'] ?? null,
                'guia_serie_numero' => $guide['guia_serie_numero'] ?? null,
            ]);
        }
    }
}
