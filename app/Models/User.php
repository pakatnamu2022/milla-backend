<?php

namespace App\Models;

use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Role;
use App\Models\gp\gestionsistema\UserRole;
use App\Models\gp\maestroGeneral\Sede;
use App\Traits\ChecksPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable, ChecksPermissions;

  protected $table = 'usr_users';

  protected $fillable = [
    'id',
    'partner_id',
    'name',
    'username',
    'password',
    'verified_at',
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
    'person.cargo_id' => 'in',
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
      'verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function person()
  {
    return $this->hasOne(Worker::class, 'id', 'partner_id')
      ->where('rrhh_persona.status_deleted', 1)
      ->where('rrhh_persona.status_id', 22);
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

  /**
   * Relación con roles (many-to-many a través de UserRole)
   * Útil para obtener TODOS los roles del usuario
   */
  public function roles()
  {
    return $this->belongsToMany(
      Role::class,
      'config_asig_role_user',
      'user_id',
      'role_id'
    )->where('config_asig_role_user.status_deleted', 1);
  }

  public function sedes()
  {
    return $this->belongsToMany(Sede::class, 'assigment_user_sede', 'user_id', 'sede_id')
      ->withPivot('status')
      ->wherePivot('status', true)
      ->whereNull('assigment_user_sede.deleted_at');
  }

  public function assignedSedes()
  {
    return $this->belongsToMany(
      Sede::class,
      'config_asig_user_sede',
      'user_id',
      'sede_id'
    )->withTimestamps()
      ->withPivot('status')
      ->wherePivot('status', true)
      ->whereNull('config_asig_user_sede.deleted_at');
  }

  public function vouchers()
  {
    return $this->belongsToMany(
      AssignSalesSeries::class,
      'user_series_assignment',
      'worker_id',
      'voucher_id'
    )->withTimestamps()->withTrashed();
  }
}
