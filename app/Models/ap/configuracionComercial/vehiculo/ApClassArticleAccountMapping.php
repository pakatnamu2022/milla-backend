<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApClassArticleAccountMapping extends Model
{
    use SoftDeletes;

    protected $table = 'ap_class_article_account_mapping';

    protected $fillable = [
        'ap_class_article_id',
        'account_type',
        'account_origin',
        'account_destination',
        'is_debit_origin',
        'status',
    ];

    protected $casts = [
        'is_debit_origin' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Relación con ApClassArticle
     */
    public function classArticle(): BelongsTo
    {
        return $this->belongsTo(ApClassArticle::class, 'ap_class_article_id');
    }

    /**
     * Scope para filtrar por tipo de cuenta
     */
    public function scopeForAccountType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope para obtener solo registros activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Obtiene la cuenta origen completa concatenada con el código de sede
     */
    public function getFullAccountOrigin(string $dynCode): string
    {
        return $this->account_origin . '-' . $dynCode;
    }

    /**
     * Obtiene la cuenta destino completa concatenada con el código de sede
     */
    public function getFullAccountDestination(string $dynCode): string
    {
        return $this->account_destination . '-' . $dynCode;
    }
}
