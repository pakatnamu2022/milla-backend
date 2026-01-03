<?php
namespace App\Models\tp\comercial;

use App\Models\BaseModel;

class DispatchStatus extends BaseModel
{
    protected $table = 'op_despacho_estados';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'norden',
        'descripcion',
        'color',
        'color2',
        'porcentaje'
    ];
}