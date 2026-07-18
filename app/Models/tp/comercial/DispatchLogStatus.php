<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class DispatchLogStatus extends BaseModel
{

    use HasFactory;

    protected $table = 'op_despacho_log_estados';


    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class, 'despacho_id');
    }

    public function dispatchState()
    {
        return $this->hasOne(DispatchStatus::class, 'id', 'estado');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'usuario');
    }
    



}