<?php
namespace App\Models\tp\comercial;


use App\Models\BaseModel;

class TravelExpense extends BaseModel
{
    protected $table = 'op_gastos_viaje';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'viaje_id',
        'ruc',
        'status_aprobacion',
        'status_observacion',
        'file',
        'extencion',
        'fileDoc',
        'liquidacion_id',
        'concepto_id',
        'monto',
        'numero_doc',
        'fecha_emision',
        'km_tanqueo',
        'punto_tanqueo_id',
        'status_deleted',
        'aprobado'
    ];

    protected $casts = [
        'created_at'  => 'date:d/m/Y H:i:s',
        'updated_at'  => 'date:d/m/Y H:i:s',
        'fecha_emision'  => 'date:d/m/Y'
    ];

}