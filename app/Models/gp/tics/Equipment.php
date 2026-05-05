<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Status;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\gp\tics\EquipmentItemAssigment;

class Equipment extends BaseModel
{

  protected $table = "help_equipos";
  protected $primaryKey = 'id';

  protected $fillable = [
    'equipo',
    'tipo_equipo_id',
    'marca',
    'modelo',
    'marca_modelo',
    'serie',
    'detalle',
    'ram',
    'almacenamiento',
    'procesador',
    'stock_actual',
    'estado_uso',
    'sede_id',
    'status_id',
    'pertenece_sede',
    'tipo_adquisicion', // CONTRATO, COMPRA
    'factura',
    'contrato',
    'proveedor',
    'fecha_adquisicion',
    'fecha_garantia',
    'status_deleted'
  ];

  const filters = [
    'id' => '=',
    'search' => ['equipo', 'marca_modelo', 'serie', 'detalle', 'factura', 'contrato'],
    'tipo_equipo_id' => '=',
    'sede_id' => '=',
    'status_id' => '=',
    'tipo_adquisicion' => '=',
    'estado_uso' => '=',
    'isAssigned' => 'accessor_bool',
  ];

  const sorts = [
    'id' => 'asc',
    'equipo' => 'asc',
    'marca_modelo' => 'asc',
    'serie' => 'asc',
    'status_id' => 'asc',
    'estado_uso' => 'asc',
    'stock_actual' => 'asc',
  ];

  public function sede()
  {
    return $this->hasOne(Sede::class, 'id', 'sede_id');
  }

  public function status()
  {
    return $this->hasOne(Status::class, 'id', 'status_id');
  }

  public function equipmentType()
  {
    return $this->hasOne(EquipmentType::class, 'id', 'tipo_equipo_id');
  }

  /**
   * Asignación activa del equipo (status_deleted = false significa activo/no desasignado).
   * Carga el worker sin global scope para poder detectar si está de baja.
   */
  public function activeAssignment()
  {
    return $this->hasOneThrough(
      EquipmentAssigment::class,
      EquipmentItemAssigment::class,
      'equipo_id',     // FK en EquipmentItemAssigment → Equipment.id
      'id',            // PK de EquipmentAssigment
      'id',            // PK de Equipment
      'asig_equipo_id' // FK en EquipmentItemAssigment → EquipmentAssigment.id
    )->where('help_asig_equipos.status_deleted', false)
      ->with(['worker' => fn($q) => $q->withoutGlobalScopes()]);
  }

  public function getIsAssignedAttribute()
  {
    return $this->activeAssignment()->exists();
  }

}
