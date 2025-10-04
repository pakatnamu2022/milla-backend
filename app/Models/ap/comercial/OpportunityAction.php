<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityAction extends Model
{
  use SoftDeletes;

  protected $table = 'opportunity_action';

  protected $fillable = [
    'opportunity_id',
    'action_type_id',
    'action_contact_type_id',
    'datetime',
    'description',
    'result',
  ];

  protected $casts = [
    'datetime' => 'datetime',
    'result' => 'boolean',
  ];

  const filters = [
    'opportunity_id' => '=',
    'action_type_id' => '=',
    'action_contact_type_id' => '=',
    'result' => '=',
  ];

  const sorts = [
    'id',
    'datetime',
    'created_at',
    'updated_at',
  ];

  public function opportunity()
  {
    return $this->belongsTo(Opportunity::class, 'opportunity_id');
  }

  public function actionType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'action_type_id');
  }

  public function actionContactType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'action_contact_type_id');
  }
}
