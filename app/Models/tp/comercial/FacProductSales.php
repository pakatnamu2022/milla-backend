<?php
namespace App\Models\tp\comercial;

use App\Models\BaseModel;

class FacProductSales extends BaseModel
{
    protected $table = "fac_producto_sales";
    protected $primaryKey = 'id'; 

    protected $casts = [
        'created_at'  => 'date:d/m/Y H:i:s',
        'updated_at'  => 'date:d/m/Y H:i:s',
    ];
}