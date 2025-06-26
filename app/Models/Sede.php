<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends BaseModel
{
    protected $table = 'config_sede';
    public $timestamps = false;

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
