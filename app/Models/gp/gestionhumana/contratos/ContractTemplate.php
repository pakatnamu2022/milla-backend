<?php

namespace App\Models\gp\gestionhumana\contratos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Plantilla de contrato, tabla legacy `rrhh_plantilla_contrato`.
 * Equivale a AdministracionPersonal/PlantillaContratoController del legacy (F5).
 *
 * Placeholders soportados en `contenido` (mergeados por ContractService::mergeTemplate):
 * {$empresa} {$RucEmpresa} {$DireccionEmpresa} {$DistritoEmpresa} {$ProvinciaEmpresa}
 * {$DepartamentoEmpresa} {$InfoEmpresa} {$abrev_suc} {$NombreTrabajador} {$DocTrabajador}
 * {$DireccionTrabajador} {$DistritoTrabajador} {$ProvinciaTrabajador} {$DepartamentoTrabajador}
 * {$EmailTrabajador} {$CargoTrabajador} {$DescCargo} {$SueldoTrabajador} {$TipoContrato}
 * {$FechInicioContrato} {$FechFinContrato} y variantes {@code _origen} (adenda/convenio) +
 * {$NombreFirmante} {$DniFirmante} {$NombreFirmanteSecundario} {$DniFirmanteSecundario}
 * (pendientes hasta implementar la firma digital, quedan vacios por ahora).
 */
class ContractTemplate extends BaseModel
{
  protected $table = 'rrhh_plantilla_contrato';

  protected $fillable = [
    'nombre',
    'descripcion',
    'contenido',
    'write_id',
    'status_deleted',
  ];

  const filters = [
    'search'      => ['nombre', 'descripcion'],
    'nombre'      => 'like',
    'descripcion' => 'like',
  ];

  const sorts = ['id', 'nombre'];

  protected static function booted(): void
  {
    static::addGlobalScope('active', fn(Builder $b) => $b->where('status_deleted', 1));
  }
}
