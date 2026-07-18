<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\tp\configuracionComercial\vehiculo\Vehiculo;
use App\Models\tp\Customer;
use App\Models\tp\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dispatch extends BaseModel
{
    use HasFactory;


    protected $table = 'op_despacho';

    public $timestamps = true;

    //falta la relacion con la tabla op_despacho_log_estados

    public function detailDispatch()
    {
        return $this->hasMany(DispatchItem::class, 'despacho_id');
    }

    public function tracto()
    {
        return $this->hasOne(Vehiculo::class, 'id', 'tracto_id');
    }

    public function carreta()
    {
        return $this->hasOne(Vehiculo::class, 'id', 'carreta_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'conductor_id');
    }

    public function customerOrigen()
    {
        return $this->hasOne(Customer::class, 'id', 'idcliente');
    }

    public function customerDestino()
    {
        return $this->hasOne(Customer::class, 'id', 'cliente_destino2_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'write_id');
    }
    


}