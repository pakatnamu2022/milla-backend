<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Status extends BaseModel
{
    use HasFactory;

    protected $table = 'config_status';
    public $timestamps = false;

    protected $fillable = [
        'norden',
        'estado',
        'tipo',
        'color',
    ];
}
