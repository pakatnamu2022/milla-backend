<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\tp\comercial\FacCitySales;
use App\Models\tp\Customer;

class OpFreight extends BaseModel
{
    protected $table = 'op_fletes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'idorigen',
        'iddestino',
        'flete',
        'tipo_flete',
        'cliente_id',
        'status_deleted'
    ];

    const filters = [
        'id' => '=',
        'search' => 'customSearch',
        'cliente_id' => '=',
        'idorigen' => '=',
        'iddestino' => '=',
        'tipo_flete' => '='
    ];

    const sorts = [
        'id' => 'asc',
        'flete' => 'asc',
        'tipo_flete' => 'asc',
        'customer_name' => 'accessor',
        'startPoint_name' => 'accessor',
        'endPoint_name' => 'accessor',
    ];

    public function scopeCustomSearch($query, $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('flete', 'like', "%{$searchTerm}%")
              ->orWhere('tipo_flete', 'like', "%{$searchTerm}%")
              ->orWhereHas('customer', function($customerQuery) use ($searchTerm) {
                $customerQuery->where('nombre_completo', 'like', "%{$searchTerm}%")
                              ->orWhere('vat', 'like', "%{$searchTerm}%");
              })
              ->orWhereHas('startPoint', function($cityQuery) use ($searchTerm)
                {
                    $cityQuery->where('descripcion', 'like', "%{$searchTerm}%");
                }
              ) 
              ->orWhereHas('endPoint', function($cityQuery) use ($searchTerm) 
                {
                    $cityQuery->where('descripcion', 'like', "%{$searchTerm}%");

                }
            );
        });
    }

    public function getCustomerNameAttribute()
    {
        return $this->customer ? $this->customer->nombre_completo : '';
    }

    public function getStartPointNameAttribute()
    {
        return $this->startPoint ? $this->startPoint->descripcion : '';
    }

    public function getEndPointNameAttribute()
    {
        return $this->endPoint ? $this->endPoint->descripcion : '';
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'cliente_id');
    }

    public function startPoint()
    {
        return $this->hasOne(FacCitySales::class, 'id', 'idorigen');
    }
    
    public function endPoint()
    {
        return $this->hasOne(FacCitySales::class, 'id', 'iddestino');
    }

}


?>