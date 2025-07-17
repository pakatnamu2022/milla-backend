<?php

namespace App\Models;

use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Role;
use App\Models\gp\gestionsistema\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usr_users';

    protected $fillable = [
        'id',
        'partner_id',
        'name',
        'username',
        'password',
        'status_deleted'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    const filters = [
        'search' => ['name', 'username', 'person.position.name', 'person.sede.razon_social', 'person.sede.suc_abrev', 'role.nombre'],
        'id' => '=',
        'name' => 'like',
        'username' => 'like',
        'person.position.name' => 'like',
        'role.nombre' => 'like',
    ];

    const sorts = [
        'id' => 'asc',
        'name' => 'asc',
        'username' => 'asc',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function person()
    {
        return $this->hasOne(Person::class, 'id', 'partner_id');
    }

    public function role()
    {
        return $this->hasOneThrough(
            Role::class,
            UserRole::class,
            'user_id', // Foreign key en la tabla UserRole
            'id', // Foreign key en la tabla Role
            'id', // Local key en la tabla User
            'role_id' // Local key en la tabla UserRole
        )->where('config_asig_role_user.status_deleted', 1);
    }
}
