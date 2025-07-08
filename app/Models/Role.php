<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends BaseModel
{
    protected $table = 'config_roles';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'creator_user',
        'updater_user',
        'status_deleted',
    ];

    const filters = [
        'search' => [
            'nombre',
            'descripcion',
        ],
        'nombre' => 'like',
        'descripcion' => 'like',
    ];

    const sorts = [
        'id' => 'asc',
        'nombre' => 'asc',
        'descripcion' => 'asc',
    ];

    public function creatorUser()
    {
        return $this->belongsTo(User::class, 'creator_user');
    }

    public function updaterUser()
    {
        return $this->belongsTo(User::class, 'updater_user');
    }
}
