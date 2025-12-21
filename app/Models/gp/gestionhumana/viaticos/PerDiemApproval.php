<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerDiemApproval extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_per_diem_approval';

  protected $fillable = [
    'per_diem_request_id',
    'approver_id',
    'status',
    'comments',
    'approved_at',
  ];

  protected $casts = [
    'approved_at' => 'datetime',
  ];

  const filters = [
    'per_diem_request_id' => '=',
    'approver_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'status',
    'approved_at',
    'created_at',
  ];

  const PENDING = 'pending';
  const APPROVED = 'approved';
  const REJECTED = 'rejected';

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
    return $this->belongsTo(Worker::class, 'approver_id');
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
}
