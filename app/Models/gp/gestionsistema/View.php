<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;

class View extends BaseModel
{
    protected $table = 'config_vista';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'descripcion',
        'submodule',
        'slug',
        'route',
        'ruta',
        'icono',
        'icon',
        'company_id',
        'parent_id',
        'idPadre',
        'idSubPadre',
        'idHijo',
        'created_at',
        'updated_at',
        'status_deleted',
    ];

    const filters = [
        'search' => ['descripcion', 'submodule', 'slug', 'route', 'ruta', 'icono', 'icon'],
        'parent_id' => '=',
        'company_id' => '=',
        'idPadre' => '=',
        'idSubPadre' => '=',
        'idHijo' => '='
    ];

    const sorts = [
        'id' => 'id',
        'descripcion' => 'descripcion',
        'submodule' => 'submodule',
        'slug' => 'slug',
        'route' => 'route',
        'ruta' => 'ruta',
        'icono' => 'icono',
        'icon' => 'icon'
    ];

    public function parent()
    {
        return $this->belongsTo(View::class, 'parent_id');
    }

    public function padre()
    {
        return $this->belongsTo(View::class, 'idPadre');
    }

    public function subPadre()
    {
        return $this->belongsTo(View::class, 'idSubPadre');
    }

    public function hijo()
    {
        return $this->belongsTo(View::class, 'idHijo');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function children()
    {
        return $this->hasMany(View::class, 'parent_id');
    }

    public function subViews()
    {
        return $this->hasMany(View::class, 'parent_id')->where('status_deleted', 1);
    }

}
