<?php
namespace App\Models\tp\comercial;

use App\Models\BaseModel;

class FacCitySales extends BaseModel
{
    protected $table = "fac_ciudades_sales";
    protected $primaryKey = 'id'; 

    protected $casts = [
        'created_at'  => 'date:d/m/Y H:i:s',
        'updated_at'  => 'date:d/m/Y H:i:s',
    ];
}