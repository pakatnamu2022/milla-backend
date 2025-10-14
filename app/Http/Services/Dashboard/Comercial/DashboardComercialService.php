<?php

namespace App\Http\Services\Dashboard\Comercial;

use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\PotentialBuyers;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para indicadores del Dashboard Comercial
 *
 * Este servicio maneja todos los indicadores relacionados con:
 * - Posibles compradores (PotentialBuyers)
 * - Oportunidades (Opportunity)
 * - Estadísticas por fechas, sedes, marcas y asesores
 */
class DashboardComercialService
{
  /**
   * Obtiene indicadores totales por rango de fechas
   *
   * @param string $dateFrom Fecha inicial (formato: Y-m-d)
   * @param string $dateTo Fecha final (formato: Y-m-d)
   * @return array
   */
  public function getTotalsByDateRange($dateFrom, $dateTo)
  {
    // Total de visitas (todos los registros en el rango)
    $totalVisits = PotentialBuyers::whereBetween('registration_date', [$dateFrom, $dateTo])->count();

    // Conteo por estado de atención
    $attentionStats = PotentialBuyers::whereBetween('registration_date', [$dateFrom, $dateTo])
      ->select('use', DB::raw('count(*) as total'))
      ->groupBy('use')
      ->get()
      ->mapWithKeys(function ($item) {
        return [$this->getUseLabel($item->use) => $item->total];
      });

    // Conteo por estado de oportunidad (a través de opportunities)
    $opportunityStats = Opportunity::whereHas('lead', function ($query) use ($dateFrom, $dateTo) {
      $query->whereBetween('registration_date', [$dateFrom, $dateTo]);
    })
      ->with('opportunityStatus')
      ->get()
      ->groupBy(function ($opportunity) {
        return $opportunity->opportunityStatus->description ?? 'SIN_ESTADO';
      })
      ->map(function ($group) {
        return $group->count();
      });

    return [
      'total_visitas' => $totalVisits,
      'no_atendidos' => $attentionStats['NO_ATENDIDO'] ?? 0,
      'atendidos' => $attentionStats['ATENDIDO'] ?? 0,
      'descartados' => $attentionStats['DESCARTADO'] ?? 0,
      'por_estado_oportunidad' => $opportunityStats->toArray(),
    ];
  }

  /**
   * Obtiene indicadores totales agrupados por sede
   *
   * @param string $dateFrom Fecha inicial (formato: Y-m-d)
   * @param string $dateTo Fecha final (formato: Y-m-d)
   * @return array
   */
  public function getTotalsBySede($dateFrom, $dateTo)
  {
    $potentialBuyers = PotentialBuyers::with(['sede'])
      ->whereBetween('registration_date', [$dateFrom, $dateTo])
      ->get()
      ->groupBy('sede_id');

    $result = [];

    foreach ($potentialBuyers as $sedeId => $buyers) {
      $sede = $buyers->first()->sede;

      // Conteo por estado de atención
      $attentionStats = $buyers->groupBy('use')->map(function ($group) {
        return $group->count();
      });

      // Conteo por estado de oportunidad
      $opportunityStats = Opportunity::whereHas('lead', function ($query) use ($buyers) {
        $query->whereIn('id', $buyers->pluck('id'));
      })
        ->with('opportunityStatus')
        ->get()
        ->groupBy(function ($opportunity) {
          return $opportunity->opportunityStatus->description ?? 'SIN_ESTADO';
        })
        ->map(function ($group) {
          return $group->count();
        });

      $result[] = [
        'sede_id' => $sedeId,
        'sede_nombre' => $sede->name ?? 'SIN_SEDE',
        'sede_abreviatura' => $sede->abreviatura ?? 'N/A',
        'total_visitas' => $buyers->count(),
        'no_atendidos' => $attentionStats[PotentialBuyers::CREATED] ?? 0,
        'atendidos' => $attentionStats[PotentialBuyers::USED] ?? 0,
        'descartados' => $attentionStats[PotentialBuyers::DISCARTED] ?? 0,
        'por_estado_oportunidad' => $opportunityStats->toArray(),
      ];
    }

    return $result;
  }

  /**
   * Obtiene indicadores agrupados por sede y marca de vehículo
   *
   * @param string $dateFrom Fecha inicial (formato: Y-m-d)
   * @param string $dateTo Fecha final (formato: Y-m-d)
   * @return array
   */
  public function getTotalsBySedeAndBrand($dateFrom, $dateTo)
  {
    $stats = PotentialBuyers::with(['sede', 'vehicleBrand'])
      ->whereBetween('registration_date', [$dateFrom, $dateTo])
      ->select('sede_id', 'vehicle_brand_id', DB::raw('count(*) as total_visitas'))
      ->groupBy('sede_id', 'vehicle_brand_id')
      ->get();

    $result = [];

    foreach ($stats as $stat) {
      $result[] = [
        'sede_id' => $stat->sede_id,
        'sede_nombre' => $stat->sede->name ?? 'SIN_SEDE',
        'sede_abreviatura' => $stat->sede->abreviatura ?? 'N/A',
        'vehicle_brand_id' => $stat->vehicle_brand_id,
        'marca_nombre' => $stat->vehicleBrand->name ?? 'SIN_MARCA',
        'total_visitas' => $stat->total_visitas,
      ];
    }

    return $result;
  }

  /**
   * Obtiene indicadores agrupados por asesor (worker)
   *
   * @param string $dateFrom Fecha inicial (formato: Y-m-d)
   * @param string $dateTo Fecha final (formato: Y-m-d)
   * @return array
   */
  public function getTotalsByAdvisor($dateFrom, $dateTo)
  {
    $stats = PotentialBuyers::with(['worker', 'sede', 'vehicleBrand'])
      ->whereBetween('registration_date', [$dateFrom, $dateTo])
      ->select(
        'worker_id',
        'sede_id',
        'vehicle_brand_id',
        'use',
        DB::raw('count(*) as total')
      )
      ->groupBy('worker_id', 'sede_id', 'vehicle_brand_id', 'use')
      ->get();

    $result = [];

    foreach ($stats as $stat) {
      $key = "{$stat->worker_id}_{$stat->sede_id}_{$stat->vehicle_brand_id}";

      if (!isset($result[$key])) {
        $result[$key] = [
          'worker_id' => $stat->worker_id,
          'worker_nombre' => $stat->worker->nombre_completo ?? 'SIN_ASESOR',
          'sede_id' => $stat->sede_id,
          'sede_nombre' => $stat->sede->name ?? 'SIN_SEDE',
          'sede_abreviatura' => $stat->sede->abreviatura ?? 'N/A',
          'vehicle_brand_id' => $stat->vehicle_brand_id,
          'marca_nombre' => $stat->vehicleBrand->name ?? 'SIN_MARCA',
          'total_visitas' => 0,
          'no_atendidos' => 0,
          'atendidos' => 0,
          'descartados' => 0,
        ];
      }

      $result[$key]['total_visitas'] += $stat->total;

      switch ($stat->use) {
        case PotentialBuyers::CREATED:
          $result[$key]['no_atendidos'] = $stat->total;
          break;
        case PotentialBuyers::USED:
          $result[$key]['atendidos'] = $stat->total;
          break;
        case PotentialBuyers::DISCARTED:
          $result[$key]['descartados'] = $stat->total;
          break;
      }
    }

    // Agregar estadísticas de oportunidades por asesor
    $opportunities = Opportunity::with(['opportunityStatus', 'lead'])
      ->whereHas('lead', function ($query) use ($dateFrom, $dateTo) {
        $query->whereBetween('registration_date', [$dateFrom, $dateTo]);
      })
      ->get();

    foreach ($result as &$advisor) {
      $advisorOpportunities = $opportunities->filter(function ($opp) use ($advisor) {
        return $opp->lead &&
          $opp->lead->worker_id == $advisor['worker_id'] &&
          $opp->lead->sede_id == $advisor['sede_id'] &&
          $opp->lead->vehicle_brand_id == $advisor['vehicle_brand_id'];
      });

      $advisor['por_estado_oportunidad'] = $advisorOpportunities
        ->groupBy(function ($opp) {
          return $opp->opportunityStatus->description ?? 'SIN_ESTADO';
        })
        ->map(function ($group) {
          return $group->count();
        })
        ->toArray();
    }

    return array_values($result);
  }

  /**
   * Obtiene la etiqueta del campo 'use'
   *
   * @param int $use
   * @return string
   */
  private function getUseLabel($use)
  {
    switch ($use) {
      case PotentialBuyers::CREATED:
        return 'NO_ATENDIDO';
      case PotentialBuyers::USED:
        return 'ATENDIDO';
      case PotentialBuyers::DISCARTED:
        return 'DESCARTADO';
      default:
        return 'DESCONOCIDO';
    }
  }
}
