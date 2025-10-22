<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\User;

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
        return $this->hasManyThrough(
            User::class,
            UserRole::class,
            'role_id', // Foreign key on UserRole table
            'id', // Foreign key on User table
            'id', // Local key on Role table
            'user_id' // Local key on UserRole table
        )->where('config_asig_role_user.status_deleted', 1);
    }

    /**
     * Relación con permisos granulares a través de role_permission (many-to-many)
     */
    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permission',
            'role_id',
            'permission_id'
        )
        ->using(RolePermission::class)
        ->withPivot('granted')
        ->withTimestamps();
    }

    /**
     * Relación con accesos básicos (CRUD) por vista
     */
    public function accesses()
    {
        return $this->hasMany(Access::class, 'role_id');
    }
}
