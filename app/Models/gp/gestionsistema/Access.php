<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    protected $table = 'config_asigxvistaxrole';

    protected $primaryKey = 'id';

    protected $fillable = [
        'vista_id',
        'role_id',
        'crear',
        'ver',
        'editar',
        'anular',
        'status_deleted',
    ];

    const filters = [
        'role_id' => '=',
        'vista_id' => '=',
    ];

    const sorts = [
        'id',
        'role_id',
        'vista_id',
    ];

    protected $casts = [
        'crear' => 'boolean',
        'ver' => 'boolean',
        'editar' => 'boolean',
        'anular' => 'boolean',
    ];
}
