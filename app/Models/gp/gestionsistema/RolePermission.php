<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo Pivot para la relación Role-Permission
 * Tabla: role_permission
 */
class RolePermission extends Pivot
{
    protected $table = 'role_permission';

    protected $fillable = [
        'role_id',
        'permission_id',
        'granted',
    ];

    protected $casts = [
        'granted' => 'boolean',
    ];

    public $timestamps = true;

    /**
     * Relación con el rol
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Relación con el permiso
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
