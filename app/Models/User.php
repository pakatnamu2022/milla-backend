<?php

namespace App\Models;

use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Role;
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
        'search' => ['name', 'username'],
        'id' => '=',
        'name' => 'like',
        'username' => 'like',
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
        return $this->hasOne(Role::class, 'id', 'role_id');
    }
}
