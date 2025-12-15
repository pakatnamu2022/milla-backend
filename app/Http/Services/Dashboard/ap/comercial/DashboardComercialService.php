<?php

namespace App\Http\Services\Dashboard\ap\comercial;

use App\Http\Resources\ap\comercial\PotentialBuyersResource;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\PotentialBuyers;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use App\Models\gp\gestionsistema\Person;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DashboardComercialService
{
  public function getTotalsByDateRangeTotal($dateFrom, $dateTo, $type)
  {
    // Total de visitas (todos los registros en el rango)
    $totalVisits = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->count();

    // Conteo por estado de atención
    $attentionStats = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->select('use', DB::raw('count(*) as total'))
      ->groupBy('use')
      ->get()
      ->mapWithKeys(function ($item) {
        return [$this->getUseLabel($item->use) => $item->total];
      });

    // Conteo por estado de oportunidad (a través de opportunities)
    $opportunityStats = Opportunity::whereHas('lead', function ($query) use ($dateFrom, $dateTo, $type) {
      $query->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
        ->where('type', $type);
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

  public function getTotalsByDateRangeGrouped($dateFrom, $dateTo, $type)
  {
    // Agrupa posibles compradores por fecha y estado de atención
    $stats = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->select(
        DB::raw('DATE(created_at) as fecha'),
        'use',
        DB::raw('count(*) as total'),
        // Calcula el promedio de segundos entre created_at y updated_at
        DB::raw('AVG(CASE
                WHEN `use` <> ' . PotentialBuyers::CREATED . '
                THEN TIMESTAMPDIFF(SECOND, created_at, updated_at)
                ELSE NULL
            END) as avg_seconds')
      )
      ->groupBy('fecha', 'use')
      ->orderBy('fecha')
      ->get();

    $result = [];
    $tiemposPromedioByFecha = []; // Para acumular tiempos por fecha

    foreach ($stats as $stat) {
      if (!isset($result[$stat->fecha])) {
        $result[$stat->fecha] = [
          'fecha' => $stat->fecha,
          'total_visitas' => 0,
          'promedio_tiempo' => "-",
          'porcentaje_atendidos' => 0,
          'estados_visita' => [
            'no_atendidos' => 0,
            'atendidos' => 0,
            'descartados' => 0,
          ],
          'por_estado_oportunidad' => [],
        ];
        $tiemposPromedioByFecha[$stat->fecha] = [
          'total_seconds' => 0,
          'count' => 0
        ];
      }

      switch ($stat->use) {
        case PotentialBuyers::CREATED:
          $result[$stat->fecha]['estados_visita']['no_atendidos'] = $stat->total;
          break;
        case PotentialBuyers::USED:
          $result[$stat->fecha]['estados_visita']['atendidos'] = $stat->total;
          break;
        case PotentialBuyers::DISCARTED:
          $result[$stat->fecha]['estados_visita']['descartados'] = $stat->total;
          break;
      }

      $result[$stat->fecha]['total_visitas'] += $stat->total;

      // Acumula los segundos promedio si existen
      if ($stat->avg_seconds !== null) {
        $tiemposPromedioByFecha[$stat->fecha]['total_seconds'] += $stat->avg_seconds * $stat->total;
        $tiemposPromedioByFecha[$stat->fecha]['count'] += $stat->total;
      }
    }

    // Calcula el promedio final por fecha, formatea y calcula el porcentaje de atendidos
    foreach ($result as $fecha => &$item) {
      // Calcula promedio de tiempo
      if ($tiemposPromedioByFecha[$fecha]['count'] > 0) {
        $avgSeconds = $tiemposPromedioByFecha[$fecha]['total_seconds'] / $tiemposPromedioByFecha[$fecha]['count'];
        $item['promedio_tiempo'] = $this->formatearTiempo($avgSeconds);
      }

      // Calcula porcentaje de atendidos
      if ($item['total_visitas'] > 0) {
        $totalAtendidos = $item['estados_visita']['atendidos'];
        $item['porcentaje_atendidos'] = round(($totalAtendidos / $item['total_visitas']) * 100, 2);
      }
    }

    // Agrupa oportunidades por fecha y estado de oportunidad
    $opportunityStats = Opportunity::whereHas('lead', function ($query) use ($dateFrom, $dateTo, $type) {
      $query->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
        ->where('type', $type);
    })
      ->with(['opportunityStatus', 'lead'])
      ->get()
      ->groupBy(function ($opportunity) {
        return $opportunity->lead && $opportunity->lead->created_at
          ? date('Y-m-d', strtotime($opportunity->lead->created_at))
          : 'SIN_FECHA';
      })
      ->map(function ($group) {
        return $group->groupBy(function ($opportunity) {
          return $opportunity->opportunityStatus->description ?? 'SIN_ESTADO';
        })->map(function ($items, $estado) {
          return [
            'estado_oportunidad' => $estado,
            'cantidad' => $items->count(),
          ];
        })->values();
      });

    // Asigna los estados de oportunidad a cada fecha
    foreach ($result as &$item) {
      $item['por_estado_oportunidad'] = $opportunityStats[$item['fecha']] ?? [];
    }

    return array_values($result);
  }

  public function getTotalsBySede($dateFrom, $dateTo, $type)
  {
    $potentialBuyers = PotentialBuyers::with(['sede'])
      ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
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
        'sede_nombre' => $sede->abreviatura ?? 'SIN_SEDE',
        'sede_abreviatura' => $sede->district->name ?? 'N/A',
        'total_visitas' => $buyers->count(),
        'no_atendidos' => $attentionStats[PotentialBuyers::CREATED] ?? 0,
        'atendidos' => $attentionStats[PotentialBuyers::USED] ?? 0,
        'descartados' => $attentionStats[PotentialBuyers::DISCARTED] ?? 0,
        'por_estado_oportunidad' => $opportunityStats->toArray(),
      ];
    }

    return $result;
  }

  public function getTotalsBySedeAndBrand($dateFrom, $dateTo, $type)
  {
    $stats = PotentialBuyers::with(['sede', 'vehicleBrand'])
      ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->select('sede_id', 'vehicle_brand_id', DB::raw('count(*) as total_visitas'))
      ->groupBy('sede_id', 'vehicle_brand_id')
      ->get();

    $result = [];

    foreach ($stats as $stat) {
      $result[] = [
        'sede_id' => $stat->sede_id,
        'sede_nombre' => $stat->sede->abreviatura ?? 'SIN_SEDE',
        'sede_abreviatura' => $stat->sede->district->name ?? 'N/A',
        'vehicle_brand_id' => $stat->vehicle_brand_id,
        'marca_nombre' => $stat->vehicleBrand->name ?? 'SIN_MARCA',
        'total_visitas' => $stat->total_visitas,
      ];
    }

    return $result;
  }

  public function getTotalsByAdvisor($dateFrom, $dateTo, $type)
  {
    $stats = PotentialBuyers::with(['worker', 'sede', 'vehicleBrand'])
      ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
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
          'sede_nombre' => $stat->sede->abreviatura ?? 'SIN_SEDE',
          'sede_abreviatura' => $stat->sede->district->name ?? 'N/A',
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
      ->whereHas('lead', function ($query) use ($dateFrom, $dateTo, $type) {
        $query->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
          ->where('type', $type);
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

  public function getTotalsByUser($dateFrom, $dateTo, $type)
  {
    $stats = PotentialBuyers::with(['user'])
      ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->select(
        'user_id',
        'use',
        DB::raw('count(*) as total')
      )
      ->groupBy('user_id', 'use')
      ->get();

    $result = [];

    foreach ($stats as $stat) {
      $userId = $stat->user_id;

      if (!isset($result[$userId])) {
        $result[$userId] = [
          'user_id' => $userId,
          'user_nombre' => $stat->user->name ?? 'SIN_USUARIO',
          'total_visitas' => 0,
          'estados_visita' => [
            'no_atendidos' => 0,
            'atendidos' => 0,
            'descartados' => 0,
          ],
          'por_estado_oportunidad' => [],
        ];
      }

      $result[$userId]['total_visitas'] += $stat->total;

      switch ($stat->use) {
        case PotentialBuyers::CREATED:
          $result[$userId]['estados_visita']['no_atendidos'] = $stat->total;
          break;
        case PotentialBuyers::USED:
          $result[$userId]['estados_visita']['atendidos'] = $stat->total;
          break;
        case PotentialBuyers::DISCARTED:
          $result[$userId]['estados_visita']['descartados'] = $stat->total;
          break;
      }
    }

    // Agregar estadísticas de oportunidades por usuario
    $potentialBuyersIds = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->pluck('id', 'user_id')
      ->groupBy(function ($item, $key) {
        return $key;
      });

    foreach ($result as $userId => &$userData) {
      $userLeadIds = PotentialBuyers::where('user_id', $userId)
        ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
        ->where('type', $type)
        ->pluck('id');

      $opportunityStats = Opportunity::whereHas('lead', function ($query) use ($userLeadIds) {
        $query->whereIn('id', $userLeadIds);
      })
        ->with('opportunityStatus')
        ->get()
        ->groupBy(function ($opportunity) {
          return $opportunity->opportunityStatus->description ?? 'SIN_ESTADO';
        })
        ->map(function ($group) {
          return $group->count();
        });

      $userData['por_estado_oportunidad'] = $opportunityStats->toArray();
    }

    return array_values($result);
  }

  public function getTotalsByCampaign($dateFrom, $dateTo, $type)
  {
    $stats = PotentialBuyers::whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->select(
        'campaign',
        'use',
        DB::raw('count(*) as total')
      )
      ->groupBy('campaign', 'use')
      ->get();

    $result = [];

    foreach ($stats as $stat) {
      $campaign = $stat->campaign ?? 'SIN_CAMPAÑA';

      if (!isset($result[$campaign])) {
        $result[$campaign] = [
          'campaign' => $campaign,
          'total_visitas' => 0,
          'estados_visita' => [
            'no_atendidos' => 0,
            'atendidos' => 0,
            'descartados' => 0,
          ],
          'por_estado_oportunidad' => [],
        ];
      }

      $result[$campaign]['total_visitas'] += $stat->total;

      switch ($stat->use) {
        case PotentialBuyers::CREATED:
          $result[$campaign]['estados_visita']['no_atendidos'] = $stat->total;
          break;
        case PotentialBuyers::USED:
          $result[$campaign]['estados_visita']['atendidos'] = $stat->total;
          break;
        case PotentialBuyers::DISCARTED:
          $result[$campaign]['estados_visita']['descartados'] = $stat->total;
          break;
      }
    }

    // Agregar estadísticas de oportunidades por campaña
    foreach ($result as $campaign => &$campaignData) {
      $campaignLeadIds = PotentialBuyers::where('campaign', $campaign === 'SIN_CAMPAÑA' ? null : $campaign)
        ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
        ->where('type', $type)
        ->pluck('id');

      $opportunityStats = Opportunity::whereHas('lead', function ($query) use ($campaignLeadIds) {
        $query->whereIn('id', $campaignLeadIds);
      })
        ->with('opportunityStatus')
        ->get()
        ->groupBy(function ($opportunity) {
          return $opportunity->opportunityStatus->description ?? 'SIN_ESTADO';
        })
        ->map(function ($group) {
          return $group->count();
        });

      $campaignData['por_estado_oportunidad'] = $opportunityStats->toArray();
    }

    return array_values($result);
  }

  /**
   * Obtiene estadísticas para jefe de ventas
   */
  public function getStatsForSalesManager($dateFrom, $dateTo, $type, $bossId = null)
  {
    // Determinar boss_id
    $bossId = $bossId ?? auth()->user()->partner_id;

    // Obtener asesores asignados
    $advisorIds = $this->getAssignedAdvisorsForManager($bossId, $dateFrom, $dateTo);

    if (empty($advisorIds)) {
      return [
        'manager_info' => $this->getManagerInfo($bossId),
        'team_totals' => [
          'total_advisors' => 0,
          'total_visits' => 0,
          'not_attended' => 0,
          'attended' => 0,
          'discarded' => 0,
          'attention_percentage' => 0,
          'by_opportunity_status' => [],
        ],
        'by_advisor' => [],
      ];
    }

    // Consultar estadísticas agrupadas por asesor
    $stats = PotentialBuyers::with(['worker'])
      ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->whereIn('worker_id', $advisorIds)
      ->select(
        'worker_id',
        'use',
        DB::raw('count(*) as total'),
        DB::raw('AVG(CASE
                WHEN `use` <> ' . PotentialBuyers::CREATED . '
                THEN TIMESTAMPDIFF(SECOND, created_at, updated_at)
                ELSE NULL
            END) as avg_response_seconds')
      )
      ->groupBy('worker_id', 'use')
      ->get();

    // Procesar estadísticas
    $byAdvisor = $this->processAdvisorStats($stats, $advisorIds, $dateFrom, $dateTo, $type);

    // Calcular totales del equipo
    $teamTotals = $this->calculateTeamTotals($byAdvisor);

    return [
      'manager_info' => $this->getManagerInfo($bossId),
      'team_totals' => $teamTotals,
      'by_advisor' => $byAdvisor,
    ];
  }

  /**
   * @param $dateFrom
   * @param $dateTo
   * @param $type
   * @param $bossId
   * @param $perPage
   * @param $workerId
   * @return AnonymousResourceCollection
   * @throws Exception
   */
  public function getDetailsForSalesManager($dateFrom, $dateTo, $type = null, $bossId = null, $perPage = 50, $workerId = null)
  {
    // Determinar boss_id
    $bossId = $bossId ?? auth()->user()->partner_id;

    // Obtener asesores asignados
    $advisorIds = $this->getAssignedAdvisorsForManager($bossId, $dateFrom, $dateTo);

    if (empty($advisorIds)) {
      throw new Exception("No hay asesores asignados para el jefe de ventas con ID {$bossId} en el período especificado.");
    }

    $query = PotentialBuyers::with([
      'worker',
      'sede',
      'sede.district',
      'vehicleBrand',
      'documentType',
      'opportunity.opportunityStatus',
    ])
      ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
      ->where('type', $type)
      ->whereIn('worker_id', $advisorIds);

    if ($workerId) {
      $query->where('worker_id', $workerId);
    }

    $query->orderBy('created_at', 'desc');

    // Paginar
    $paginated = $query->paginate($perPage);

    return PotentialBuyersResource::collection($paginated);
  }

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

  private function formatearTiempo($seconds)
  {
    $dias = floor($seconds / 86400);
    $horas = floor(($seconds % 86400) / 3600);
    $minutos = floor(($seconds % 3600) / 60);

    $partes = [];
    if ($dias > 0) $partes[] = $dias . 'd';
    if ($horas > 0) $partes[] = $horas . 'h';
    if ($minutos > 0 || empty($partes)) $partes[] = $minutos . 'm';

    return implode(' ', $partes);
  }

  /**
   * Genera lista de períodos (año, mes) dentro del rango de fechas
   */
  private function generatePeriods($dateFrom, $dateTo)
  {
    $periods = [];
    $start = Carbon::parse($dateFrom);
    $end = Carbon::parse($dateTo);

    while ($start <= $end) {
      $periods[] = [
        'year' => $start->year,
        'month' => $start->month
      ];
      $start->addMonth();
    }

    return $periods;
  }

  /**
   * Obtiene IDs de asesores asignados a un jefe en un rango de fechas
   */
  private function getAssignedAdvisorsForManager($bossId, $dateFrom, $dateTo)
  {
    $periods = $this->generatePeriods($dateFrom, $dateTo);

    $query = ApAssignmentLeadership::where('boss_id', $bossId)
      ->where('status', 1);

    $query->where(function ($q) use ($periods) {
      foreach ($periods as $period) {
        $q->orWhere(function ($subQuery) use ($period) {
          $subQuery->where('year', $period['year'])
            ->where('month', $period['month']);
        });
      }
    });

    $workerIds = $query->distinct()
      ->pluck('worker_id')
      ->toArray();

    return $workerIds;
  }

  /**
   * Obtiene información del jefe de ventas
   */
  private function getManagerInfo($bossId)
  {
    $manager = Person::with('position')->find($bossId);

    if (!$manager) {
      return [
        'boss_id' => $bossId,
        'boss_name' => 'Desconocido',
        'boss_position' => 'N/A'
      ];
    }

    return [
      'boss_id' => $manager->id,
      'boss_name' => $manager->nombre_completo ?? 'N/A',
      'boss_position' => $manager->position->nombre_cargo ?? 'N/A'
    ];
  }

  /**
   * Procesa estadísticas agrupadas por asesor
   */
  private function processAdvisorStats($stats, $advisorIds, $dateFrom, $dateTo, $type)
  {
    $result = [];

    // Cargar información de todos los asesores asignados
    $workers = Person::whereIn('id', $advisorIds)->get()->keyBy('id');

    // Inicializar estructura para todos los asesores asignados
    foreach ($advisorIds as $workerId) {
      $worker = $workers->get($workerId);
      $result[$workerId] = [
        'worker_id' => $workerId,
        'worker_name' => $worker ? $worker->nombre_completo : 'N/A',
        'total_visits' => 0,
        'not_attended' => 0,
        'attended' => 0,
        'discarded' => 0,
        'attention_percentage' => 0,
        'average_response_time' => '-',
        'total_seconds' => 0,
        'count_with_time' => 0,
      ];
    }

    // Procesar estadísticas
    foreach ($stats as $stat) {
      $workerId = $stat->worker_id;

      if (!isset($result[$workerId])) {
        $result[$workerId] = [
          'worker_id' => $workerId,
          'worker_name' => $stat->worker->nombre_completo ?? 'N/A',
          'total_visits' => 0,
          'not_attended' => 0,
          'attended' => 0,
          'discarded' => 0,
          'attention_percentage' => 0,
          'average_response_time' => '-',
          'total_seconds' => 0,
          'count_with_time' => 0,
        ];
      }

      $result[$workerId]['worker_name'] = $stat->worker->nombre_completo ?? 'N/A';
      $result[$workerId]['total_visits'] += $stat->total;

      switch ($stat->use) {
        case PotentialBuyers::CREATED:
          $result[$workerId]['not_attended'] = $stat->total;
          break;
        case PotentialBuyers::USED:
          $result[$workerId]['attended'] = $stat->total;
          break;
        case PotentialBuyers::DISCARTED:
          $result[$workerId]['discarded'] = $stat->total;
          break;
      }

      // Acumular tiempo promedio
      if ($stat->avg_response_seconds !== null) {
        $result[$workerId]['total_seconds'] += $stat->avg_response_seconds * $stat->total;
        $result[$workerId]['count_with_time'] += $stat->total;
      }
    }

    // Calcular porcentajes y tiempos promedio finales
    foreach ($result as &$advisor) {
      if ($advisor['total_visits'] > 0) {
        $advisor['attention_percentage'] = round(($advisor['attended'] / $advisor['total_visits']) * 100, 2);
      }

      if ($advisor['count_with_time'] > 0) {
        $avgSeconds = $advisor['total_seconds'] / $advisor['count_with_time'];
        $advisor['average_response_time'] = $this->formatearTiempo($avgSeconds);
      }

      // Remover campos temporales
      unset($advisor['total_seconds']);
      unset($advisor['count_with_time']);
    }

    // Enriquecer con datos de oportunidades
    $this->enrichAdvisorWithOpportunityStats($result, $advisorIds, $dateFrom, $dateTo, $type);

    return array_values($result);
  }

  /**
   * Enriquece las estadísticas de asesores con datos de oportunidades
   */
  private function enrichAdvisorWithOpportunityStats(&$result, $advisorIds, $dateFrom, $dateTo, $type)
  {
    $opportunities = Opportunity::with(['opportunityStatus', 'lead'])
      ->whereHas('lead', function ($query) use ($dateFrom, $dateTo, $type, $advisorIds) {
        $query->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
          ->where('type', $type)
          ->whereIn('worker_id', $advisorIds);
      })
      ->get();

    foreach ($result as &$advisor) {
      $advisorOpportunities = $opportunities->filter(function ($opp) use ($advisor) {
        return $opp->lead && $opp->lead->worker_id == $advisor['worker_id'];
      });

      $advisor['by_opportunity_status'] = $advisorOpportunities
        ->groupBy(function ($opp) {
          return $opp->opportunityStatus->description ?? 'SIN_ESTADO';
        })
        ->map(function ($group) {
          return $group->count();
        })
        ->toArray();
    }
  }

  /**
   * Calcula totales del equipo
   */
  private function calculateTeamTotals($byAdvisor)
  {
    $totals = [
      'total_advisors' => count($byAdvisor),
      'total_visits' => 0,
      'not_attended' => 0,
      'attended' => 0,
      'discarded' => 0,
      'attention_percentage' => 0,
      'by_opportunity_status' => [],
    ];

    foreach ($byAdvisor as $advisor) {
      $totals['total_visits'] += $advisor['total_visits'];
      $totals['not_attended'] += $advisor['not_attended'];
      $totals['attended'] += $advisor['attended'];
      $totals['discarded'] += $advisor['discarded'];

      // Agregar estados de oportunidad
      if (isset($advisor['by_opportunity_status'])) {
        foreach ($advisor['by_opportunity_status'] as $estado => $cantidad) {
          if (!isset($totals['by_opportunity_status'][$estado])) {
            $totals['by_opportunity_status'][$estado] = 0;
          }
          $totals['by_opportunity_status'][$estado] += $cantidad;
        }
      }
    }

    // Calcular porcentaje de atención del equipo
    if ($totals['total_visits'] > 0) {
      $totals['attention_percentage'] = round(($totals['attended'] / $totals['total_visits']) * 100, 2);
    }

    return $totals;
  }
}
