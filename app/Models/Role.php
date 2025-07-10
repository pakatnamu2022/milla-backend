<?php

namespace App\Models;

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

    public function users()
    {
        return $this->hasMany(UserRole::class, 'role_id')->where('status_deleted', 1);
    }
}
