<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\DigitalFile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyPhoto extends BaseModel
{
    protected $table = 'op_supply_photo';

    public $timestamps = true;

    protected $fillable = [
        'supply_control_id',
        'digital_file_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'photo_type',
        'uploaded_at',
        'uploaded_by',
        'status_deleted',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    const TYPE_TANK_LEFT = 'tank_left';

    const TYPE_TANK_RIGHT = 'tank_right';

    const TYPE_TICKET = 'ticket';

    public function supplyControl(): BelongsTo
    {
        return $this->belongsTo(SupplyControl::class, 'supply_control_id');
    }

    public function digitalFile(): BelongsTo
    {
        return $this->belongsTo(DigitalFile::class, 'digital_file_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('photo_type', $type);
    }

    public function scopeTankPhotos($query)
    {
        return $query->whereIn('photo_type', [self::TYPE_TANK_LEFT, self::TYPE_TANK_RIGHT]);
    }

    public function scopeTicketPhoto($query)
    {
        return $query->where('photo_type', self::TYPE_TICKET);
    }

    public function getPublicUrlAttribute(): ?string
    {
        return $this->digitalFile?->url ?? null;
    }

    public function getFileNameAttribute(): ?string
    {
        return $this->digitalFile?->name ?? $this->file_name;
    }

    public function getMimeTypeAttribute(): ?string
    {
        return $this->digitalFile?->mimeType ?? $this->mime_type;
    }

    public function getPathAttribute(): ?string
    {
        return $this->digitalFile?->name ?? $this->file_path;
    }
}
