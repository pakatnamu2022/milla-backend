<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectronicDocumentInternalNote extends BaseModel
{
    protected $table = 'electronic_document_internal_notes';

    protected $fillable = [
        'electronic_document_id',
        'internal_note_id',
    ];

    public $timestamps = true;

    /**
     * Relationships
     */
    public function electronicDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'electronic_document_id');
    }

    public function internalNote(): BelongsTo
    {
        return $this->belongsTo(ApInternalNote::class, 'internal_note_id');
    }
}