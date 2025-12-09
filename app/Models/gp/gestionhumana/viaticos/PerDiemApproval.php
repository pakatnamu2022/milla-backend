<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerDiemApproval extends BaseModel
{
  use SoftDeletes;

  protected $fillable = [
    'per_diem_request_id',
    'approver_id',
    'approver_type',
    'status',
    'comments',
    'approved_at',
  ];

  protected $casts = [
    'approved_at' => 'datetime',
  ];

  /**
   * Get the per diem request this approval belongs to
   */
  public function request(): BelongsTo
  {
    return $this->belongsTo(PerDiemRequest::class, 'per_diem_request_id');
  }

  /**
   * Get the user who is the approver
   */
  public function approver(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approver_id');
  }

  /**
   * Scope to filter pending approvals
   */
  public function scopePending($query)
  {
    return $query->where('status', 'pending');
  }

  /**
   * Scope to filter approvals by approver
   */
  public function scopeByApprover($query, int $approverId)
  {
    return $query->where('approver_id', $approverId);
  }

  /**
   * Scope to filter approvals by type
   */
  public function scopeByType($query, string $type)
  {
    return $query->where('approver_type', $type);
  }
}
