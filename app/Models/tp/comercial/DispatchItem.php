<?php
namespace App\Models\tp\comercial;

use App\Models\BaseModel;

class DispatchItem extends BaseModel
{
    protected $table = 'op_despacho_item'; 
    public $timestamps = false;
    protected $fillable = [
        'id',
        'despacho_id',
        'idorigen',
        'iddestino',
        'idproducto',
        'observacion',
        'tiempo_estimado',
        'tipo_flete',
        'unidad_medida_id',
        'km_viaje',
        'cantidad',
        'precio_unit',
        'total',
        'created_at',
        'updated_at',
    ];

    public function product()
    {       
        return $this->hasOne(FacProductSales::class, 'id', 'idproducto');
    }

    public function origin()
    {       
        return $this->hasOne(FacCitySales::class, 'id', 'idorigen');
    }

    public function destination()
    {       
        return $this->hasOne(FacCitySales::class, 'id', 'iddestino');
    }
 
}