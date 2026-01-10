<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;

class DispatchStatus extends BaseModel
{
  protected $table = 'op_despacho_estados';
  protected $primaryKey = 'id';

  protected $fillable = [
    'id',
    'norden',
    'descripcion',
    'color',
    'color2',
    'porcentaje'
  ];


  const FILTER_ALL = 'all';
  const FILTER_PENDING = 'pending';
  const FILTER_IN_PROGRESS = 'in_progress';
  const FILTER_FUEL_PENDING = 'fuel_pending';
  const FILTER_COMPLETED = 'completed';

  //mapeo de estados
  const STATUS_PENDING = 1; //pendiente
  const STATUS_SCHEDULED = 2; //programado
  const STATUS_EN_ROUTE = 3; //en ruta
  const STATUS_AT_ORIGIN = 4; // en origen
  const STATUS_LOADING = 5; // cargando
  const STATUS_IN_TRANSIT = 6; // en transito
  const STATUS_UNLOADING = 7; // descargando
  const STATUS_FUEL_PENDING = 8; // combustible pendiente
  const STATUS_COMPLETED = 9; // finalizado
  const STATUS_CANCELLED = 10; // anulado
  const STATUS_LIQUIDATED = 11; // liquidado

  public static function getStatusMapping(): array
  {
    return [
      self::STATUS_PENDING => self::FILTER_PENDING,
      self::STATUS_SCHEDULED => self::FILTER_PENDING,
      self::STATUS_EN_ROUTE => self::FILTER_IN_PROGRESS,
      self::STATUS_AT_ORIGIN => self::FILTER_IN_PROGRESS,
      self::STATUS_LOADING => self::FILTER_IN_PROGRESS,
      self::STATUS_IN_TRANSIT => self::FILTER_IN_PROGRESS,
      self::STATUS_UNLOADING => self::FILTER_IN_PROGRESS,
      self::STATUS_FUEL_PENDING => self::FILTER_FUEL_PENDING,
      self::STATUS_COMPLETED => self::FILTER_COMPLETED,
      self::STATUS_LIQUIDATED => self::FILTER_COMPLETED,
      self::STATUS_CANCELLED => 'cancelled',
    ];
  }

  public static function toTripStatus(int $databaseStatus): string
  {
    $mapping = self::getStatusMapping();
    return $mapping[$databaseStatus] ?? self::FILTER_IN_PROGRESS;
  }

  public static function fromTripStatus(string $tripStatus): array
  {
    $mapping = self::getStatusMapping();
    $result = [];

    foreach ($mapping as $dbStatus => $frontendStatus) {
      if ($frontendStatus === $tripStatus) {
        $result[] = $dbStatus;
      }
    }

    return $result;
  }

  public static function getInProgressStatuses(): array
  {
    return [
      self::STATUS_EN_ROUTE,
      self::STATUS_AT_ORIGIN,
      self::STATUS_LOADING,
      self::STATUS_IN_TRANSIT,
      self::STATUS_UNLOADING
    ];
  }

  public static function isActiveStatus(int $status): bool
  {
    $inactive = [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_LIQUIDATED];
    return !in_array($status, $inactive);
  }

  public static function getDisplayName(int $status): string
  {
    $names = [
      self::STATUS_PENDING => 'Pendiente',
      self::STATUS_SCHEDULED => 'Programado',
      self::STATUS_EN_ROUTE => 'En Ruta',
      self::STATUS_AT_ORIGIN => 'En Origen',
      self::STATUS_LOADING => 'Cargando',
      self::STATUS_IN_TRANSIT => 'En Tránsito',
      self::STATUS_UNLOADING => 'Descargando',
      self::STATUS_FUEL_PENDING => 'Combustible Pendiente',
      self::STATUS_COMPLETED => 'Finalizado',
      self::STATUS_CANCELLED => 'Anulado',
      self::STATUS_LIQUIDATED => 'Liquidado'
    ];

    return $names[$status] ?? 'Desconocido';
  }


}
