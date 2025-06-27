<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends BaseModel
{
    protected $table = 'config_sede';
    public $timestamps = false;

    protected $fillable = [
        'localidad',
        'suc_abrev',
        'abreviatura',
        'empresa_id',
        'ruc',
        'razon_social',
        'direccion',
        'distrito',
        'provincia',
        'departamento',
        'web',
        'email',
        'logo',
        'ciudad',
        'info_labores'
    ];

    const filters = [
        'id' => '=',
        'search' => ['suc_abrev', 'abreviatura', 'razon_social', 'direccion', 'ciudad'],
        'empresa_id' => '=',
    ];

    const sorts = [
        'id',
        'suc_abrev',
        'abreviatura',
        'razon_social',
        'direccion',
        'ciudad',
    ];

    public function areas()
    {
        //return $this->belongsTo(Area::class);
        return $this->hasMany(Area::class, 'sede_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'empresa_id', 'id');
    }
}
