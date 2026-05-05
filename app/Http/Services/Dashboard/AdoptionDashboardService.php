<?php

namespace App\Http\Services\Dashboard;

use App\Models\AuditLogs;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdoptionDashboardService
{
  // Points per action
  private const ACTION_POINTS = [
    'created' => 5,
    'updated' => 2,
    'deleted' => 1,
    'restored' => 3,
  ];

  // Score formula weights
  private const WEIGHTS = [
    'frequency' => 0.20,
    'persistence' => 0.40,
    'compliance' => 0.30,
    'quality' => 0.10,
  ];

  // Daily targets for normalization
  private const DAILY_TARGET_POINTS = 10;   // action_score / day
  private const DAILY_TARGET_OPS = 3;    // ops / day
  private const TARGET_MODULES = 4;    // unique module paths

  // Adoption score thresholds
  private const CHAMPION_THRESHOLD = 85;
  private const POWERUSER_THRESHOLD = 70;
  private const RISK_HIGH = 30;
  private const RISK_MEDIUM = 55;

  private const MODULE_LABELS = [
    'ap' => [
      'comercial' => 'AP - Comercial',
      'compras' => 'AP - Compras',
      'facturacion' => 'AP - Facturación',
      'maestroGeneral' => 'AP - Maestro General',
      'postventa' => 'AP - Post Venta',
      'configuracionComercial' => 'AP - Config. Comercial',
    ],
    'gp' => [
      'gestionhumana' => 'GP - Gestión Humana',
      'gestionsistema' => 'GP - Gestión Sistema',
      'maestroGeneral' => 'GP - Maestro General',
      'tics' => 'GP - TICs',
    ],
    'tp' => [
      'comercial' => 'TP - Comercial',
    ],
  ];

  // -------------------------------------------------------------------------
  // Public API
  // -------------------------------------------------------------------------

  public function getExecutiveSummary(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);
    $periodDays = max(1, $from->diffInDays($to) + 1);

    $baseQuery = $this->baseQuery($from, $to, $filters);

    $totalOps = (clone $baseQuery)->count();
    $activeUsers = (clone $baseQuery)->distinct('user_id')->count('user_id');

    // Sede with highest adoption (most ops)
    $topSede = (clone $baseQuery)
      ->select('s.localidad as sede_name', DB::raw('COUNT(*) as ops'))
      ->join('usr_users as u', 'audit_logs.user_id', '=', 'u.id')
      ->join('rrhh_persona as w', 'u.partner_id', '=', 'w.id')
      ->join('config_sede as s', 'w.sede_id', '=', 's.id')
      ->groupBy('s.id', 's.localidad')
      ->orderByDesc('ops')
      ->first();

    // Trend comparison: current period vs previous period of same length
    $prevFrom = $from->copy()->subDays($periodDays);
    $prevTo = $from->copy()->subDay();
    $prevOps = AuditLogs::whereBetween('created_at', [$prevFrom, $prevTo])
      ->whereIn('action', array_keys(self::ACTION_POINTS))
      ->count();

    $trend7 = $this->trendSummary($filters, 7);
    $trend30 = $this->trendSummary($filters, 30);

    // Total registered users (expected adopters)
    $expectedUsers = User::whereNull('status_deleted')->count();

    $globalScore = $activeUsers > 0
      ? $this->computeGlobalScore($from, $to, $filters)
      : 0;

    return [
      'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
      'active_users' => $activeUsers,
      'expected_users' => $expectedUsers,
      'total_ops' => $totalOps,
      'top_sede' => $topSede ? $topSede->sede_name : null,
      'global_adoption_index' => round($globalScore, 1),
      'trend_vs_previous_period' => $prevOps > 0
        ? round((($totalOps - $prevOps) / $prevOps) * 100, 1)
        : null,
      'trend_7_days' => $trend7,
      'trend_30_days' => $trend30,
    ];
  }

  public function getUserRanking(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);
    $periodDays = max(1, $from->diffInDays($to) + 1);

    $rows = $this->rawUserStats($from, $to, $filters);
    $totalModules = $this->countDistinctModules($from, $to, $filters);

    return $rows->map(function ($row) use ($periodDays, $totalModules) {
      $score = $this->computeUserScore($row, $periodDays, $totalModules);
      return [
        'user_id' => $row->user_id,
        'user_name' => $row->user_name,
        'sede_id' => $row->sede_id,
        'sede_name' => $row->sede_name ?? 'Sin sede',
        'total_ops' => (int)$row->total_ops,
        'creates' => (int)$row->creates,
        'updates' => (int)$row->updates,
        'deletes' => (int)$row->deletes,
        'action_score' => (int)$row->action_score,
        'active_days' => (int)$row->active_days,
        'unique_modules' => (int)$row->unique_modules,
        'modules_list' => $this->decodeModules($row->modules_raw ?? ''),
        'adoption_score' => round($score['total'], 1),
        'score_breakdown' => $score['breakdown'],
        'badge' => $this->assignBadge($score['total'], (int)$row->unique_modules, (int)$row->active_days, $periodDays),
      ];
    })
      ->sortByDesc('adoption_score')
      ->values()
      ->all();
  }

  public function getSedeRanking(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);
    $periodDays = max(1, $from->diffInDays($to) + 1);

    $rows = DB::table('audit_logs as al')
      ->select(
        's.id as sede_id',
        's.localidad as sede_name',
        's.suc_abrev as sede_code',
        DB::raw('COUNT(*) as total_ops'),
        DB::raw('COUNT(DISTINCT al.user_id) as active_users'),
        DB::raw('SUM(al.action = \'created\') as creates'),
        DB::raw('SUM(al.action = \'updated\') as updates'),
        DB::raw('SUM(al.action = \'deleted\') as deletes'),
        DB::raw('SUM(
                    CASE al.action
                        WHEN \'created\'  THEN 5
                        WHEN \'updated\'  THEN 2
                        WHEN \'deleted\'  THEN 1
                        WHEN \'restored\' THEN 3
                        ELSE 0
                    END
                ) as action_score'),
        DB::raw('COUNT(DISTINCT DATE(al.created_at)) as active_days'),
        DB::raw('COUNT(DISTINCT al.auditable_type) as unique_models')
      )
      ->join('usr_users as u', 'al.user_id', '=', 'u.id')
      ->join('rrhh_persona as w', 'u.partner_id', '=', 'w.id')
      ->join('config_sede as s', 'w.sede_id', '=', 's.id')
      ->whereIn('al.action', array_keys(self::ACTION_POINTS))
      ->whereBetween('al.created_at', [$from, $to])
      ->when(isset($filters['sede_id']), fn($q) => $q->where('s.id', $filters['sede_id']))
      ->groupBy('s.id', 's.localidad', 's.suc_abrev')
      ->orderByDesc('action_score')
      ->get();

    // Expected users per sede
    $expectedBySede = DB::table('rrhh_persona as w')
      ->select('w.sede_id', DB::raw('COUNT(DISTINCT u.id) as expected'))
      ->join('usr_users as u', 'w.id', '=', 'u.partner_id')
      ->whereNull('u.status_deleted')
      ->groupBy('w.sede_id')
      ->pluck('expected', 'sede_id');

    $totalModules = $this->countDistinctModules($from, $to, $filters);

    return $rows->map(function ($row) use ($periodDays, $expectedBySede, $totalModules) {
      $expected = $expectedBySede[$row->sede_id] ?? 1;
      $opsPerUser = $row->active_users > 0
        ? round($row->total_ops / $row->active_users, 1)
        : 0;

      $score = $this->computeSedeScore($row, $periodDays, $expected, $totalModules);

      return [
        'sede_id' => $row->sede_id,
        'sede_name' => $row->sede_name,
        'sede_code' => $row->sede_code,
        'total_ops' => (int)$row->total_ops,
        'active_users' => (int)$row->active_users,
        'expected_users' => (int)$expected,
        'ops_per_user' => $opsPerUser,
        'unique_models' => (int)$row->unique_models,
        'adoption_index' => round($score, 1),
        'compliance_semaphore' => $this->semaphore($score),
      ];
    })->all();
  }

  public function getModuleUsage(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);

    $rows = $this->baseQuery($from, $to, $filters)
      ->select(
        'audit_logs.auditable_type',
        DB::raw('COUNT(*) as total_ops'),
        DB::raw('COUNT(DISTINCT audit_logs.user_id) as users'),
        DB::raw('COUNT(DISTINCT DATE(audit_logs.created_at)) as active_days'),
        DB::raw('SUM(audit_logs.action = \'created\') as creates'),
        DB::raw('SUM(audit_logs.action = \'updated\') as updates'),
        DB::raw('SUM(audit_logs.action = \'deleted\') as deletes')
      )
      ->groupBy('audit_logs.auditable_type')
      ->orderByDesc('total_ops')
      ->get();

    // Group by module/submodule
    $grouped = [];
    foreach ($rows as $row) {
      $module = $this->extractModuleLabel($row->auditable_type);
      if (!isset($grouped[$module])) {
        $grouped[$module] = [
          'module' => $module,
          'total_ops' => 0,
          'users' => 0,
          'active_days' => 0,
          'creates' => 0,
          'updates' => 0,
          'deletes' => 0,
          'models' => [],
        ];
      }
      $grouped[$module]['total_ops'] += $row->total_ops;
      $grouped[$module]['users'] = max($grouped[$module]['users'], $row->users);
      $grouped[$module]['active_days'] = max($grouped[$module]['active_days'], $row->active_days);
      $grouped[$module]['creates'] += $row->creates;
      $grouped[$module]['updates'] += $row->updates;
      $grouped[$module]['deletes'] += $row->deletes;
      $grouped[$module]['models'][] = [
        'model' => class_basename($row->auditable_type),
        'total_ops' => (int)$row->total_ops,
        'users' => (int)$row->users,
      ];
    }

    $totalOps = array_sum(array_column($grouped, 'total_ops'));

    return array_values(array_map(function ($m) use ($totalOps) {
      $m['share_pct'] = $totalOps > 0 ? round($m['total_ops'] / $totalOps * 100, 1) : 0;
      return $m;
    }, $grouped));
  }

  public function getCompliance(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);
    $periodDays = max(1, $from->diffInDays($to) + 1);
    $expectedOpsPerUser = (int)($filters['expected_ops_per_day'] ?? self::DAILY_TARGET_OPS) * $periodDays;

    $userStats = $this->rawUserStats($from, $to, $filters);

    $expectedUsers = User::whereNull('status_deleted')->pluck('name', 'id');
    $activeUserIds = $userStats->pluck('user_id')->flip();

    $result = $userStats->map(function ($row) use ($expectedOpsPerUser) {
      $ratio = $expectedOpsPerUser > 0 ? min($row->total_ops / $expectedOpsPerUser, 1) : 0;
      return [
        'user_id' => $row->user_id,
        'user_name' => $row->user_name,
        'sede_name' => $row->sede_name ?? 'Sin sede',
        'actual_ops' => (int)$row->total_ops,
        'expected_ops' => $expectedOpsPerUser,
        'compliance_pct' => round($ratio * 100, 1),
        'semaphore' => $this->semaphore($ratio * 100),
      ];
    })->sortByDesc('compliance_pct')->values();

    // Inactive expected users
    $inactive = $expectedUsers->filter(fn($name, $id) => !isset($activeUserIds[$id]))
      ->map(fn($name, $id) => [
        'user_id' => $id,
        'user_name' => $name,
        'sede_name' => null,
        'actual_ops' => 0,
        'expected_ops' => $expectedOpsPerUser,
        'compliance_pct' => 0,
        'semaphore' => 'red',
      ])->values();

    return [
      'active_compliance' => $result->all(),
      'inactive_users' => $inactive->all(),
      'summary' => [
        'green' => $result->where('semaphore', 'green')->count(),
        'yellow' => $result->where('semaphore', 'yellow')->count(),
        'red' => $result->where('semaphore', 'red')->count() + $inactive->count(),
      ],
    ];
  }

  public function getChampionsAndAtRisk(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);
    $periodDays = max(1, $from->diffInDays($to) + 1);
    $totalModules = $this->countDistinctModules($from, $to, $filters);

    $rows = $this->rawUserStats($from, $to, $filters);

    $champions = [];
    $atRisk = [];

    foreach ($rows as $row) {
      $score = $this->computeUserScore($row, $periodDays, $totalModules);
      $s = $score['total'];
      $badge = $this->assignBadge($s, (int)$row->unique_modules, (int)$row->active_days, $periodDays);

      $entry = [
        'user_id' => $row->user_id,
        'user_name' => $row->user_name,
        'sede_name' => $row->sede_name ?? 'Sin sede',
        'adoption_score' => round($s, 1),
        'total_ops' => (int)$row->total_ops,
        'active_days' => (int)$row->active_days,
        'unique_modules' => (int)$row->unique_modules,
      ];

      if ($badge) {
        $champions[] = array_merge($entry, ['badge' => $badge]);
      } elseif ($s < self::RISK_MEDIUM) {
        $risk = $s < self::RISK_HIGH ? 'alto' : 'medio';
        $atRisk[] = array_merge($entry, ['risk_level' => $risk]);
      }
    }

    // Users with zero activity (expected but not in audit_logs)
    $activeIds = $rows->pluck('user_id')->flip();
    $zeroActivity = User::whereNull('status_deleted')
      ->whereNotIn('id', $activeIds->keys()->all())
      ->with(['person.sede'])
      ->limit(100)
      ->get()
      ->map(fn($u) => [
        'user_id' => $u->id,
        'user_name' => $u->name,
        'sede_name' => optional(optional($u->person)->sede)->localidad ?? 'Sin sede',
        'adoption_score' => 0,
        'total_ops' => 0,
        'active_days' => 0,
        'unique_modules' => 0,
        'risk_level' => 'alto',
      ]);

    usort($champions, fn($a, $b) => $b['adoption_score'] <=> $a['adoption_score']);
    usort($atRisk, fn($a, $b) => $a['adoption_score'] <=> $b['adoption_score']);

    return [
      'champions' => $champions,
      'at_risk' => array_merge($atRisk, $zeroActivity->all()),
      'champion_count' => count($champions),
      'at_risk_count' => count($atRisk) + $zeroActivity->count(),
    ];
  }

  public function getAlerts(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);
    $periodDays = max(1, $from->diffInDays($to) + 1);
    $totalModules = $this->countDistinctModules($from, $to, $filters);

    $alerts = [];

    // 1. Trained users with zero activity
    $activeIds = $this->baseQuery($from, $to, $filters)
      ->distinct('user_id')->pluck('user_id');
    $inactiveCount = User::whereNull('status_deleted')
      ->whereNotIn('id', $activeIds)->count();
    if ($inactiveCount > 0) {
      $alerts[] = [
        'type' => 'inactive_trained_users',
        'severity' => 'high',
        'message' => "$inactiveCount usuario(s) capacitado(s) sin ninguna actividad en el período.",
        'count' => $inactiveCount,
      ];
    }

    // 2. Lagging sedes (adoption index < 40)
    $sedeRanking = $this->getSedeRanking($filters);
    $laggingSedes = array_filter($sedeRanking, fn($s) => $s['adoption_index'] < 40);
    foreach ($laggingSedes as $sede) {
      $alerts[] = [
        'type' => 'lagging_sede',
        'severity' => 'medium',
        'message' => "Sede «{$sede['sede_name']}» con índice de adopción bajo ({$sede['adoption_index']}%).",
        'sede' => $sede['sede_name'],
      ];
    }

    // 3. Underused modules (less than 3% of total ops)
    $moduleUsage = $this->getModuleUsage($filters);
    $underused = array_filter($moduleUsage, fn($m) => $m['share_pct'] < 3 && $m['total_ops'] < 10);
    foreach ($underused as $mod) {
      $alerts[] = [
        'type' => 'underused_module',
        'severity' => 'low',
        'message' => "Módulo «{$mod['module']}» con muy baja adopción ({$mod['total_ops']} ops).",
        'module' => $mod['module'],
      ];
    }

    // 4. Activity drop: compare last 7 days vs previous 7 days
    $last7From = $to->copy()->subDays(6);
    $prev7From = $last7From->copy()->subDays(7);
    $prev7To = $last7From->copy()->subDay();
    $last7Ops = AuditLogs::whereBetween('created_at', [$last7From, $to])
      ->whereIn('action', array_keys(self::ACTION_POINTS))->count();
    $prev7Ops = AuditLogs::whereBetween('created_at', [$prev7From, $prev7To])
      ->whereIn('action', array_keys(self::ACTION_POINTS))->count();
    if ($prev7Ops > 0 && $last7Ops < $prev7Ops * 0.7) {
      $drop = round((1 - $last7Ops / $prev7Ops) * 100);
      $alerts[] = [
        'type' => 'activity_drop',
        'severity' => 'high',
        'message' => "Caída de actividad del {$drop}% en los últimos 7 días vs. los 7 días anteriores.",
        'drop_pct' => $drop,
      ];
    }

    return $alerts;
  }

  public function getTrend(array $filters): array
  {
    [$from, $to] = $this->resolveDateRange($filters);

    $daily = $this->baseQuery($from, $to, $filters)
      ->select(
        DB::raw('DATE(audit_logs.created_at) as day'),
        DB::raw('COUNT(*) as total_ops'),
        DB::raw('COUNT(DISTINCT audit_logs.user_id) as active_users'),
        DB::raw('SUM(audit_logs.action = \'created\') as creates'),
        DB::raw('SUM(audit_logs.action = \'updated\') as updates'),
        DB::raw('SUM(audit_logs.action = \'deleted\') as deletes')
      )
      ->groupBy('day')
      ->orderBy('day')
      ->get();

    return $daily->map(fn($d) => [
      'day' => $d->day,
      'total_ops' => (int)$d->total_ops,
      'active_users' => (int)$d->active_users,
      'creates' => (int)$d->creates,
      'updates' => (int)$d->updates,
      'deletes' => (int)$d->deletes,
    ])->all();
  }

  // -------------------------------------------------------------------------
  // Private helpers
  // -------------------------------------------------------------------------

  private function resolveDateRange(array $filters): array
  {
    $from = isset($filters['date_from'])
      ? Carbon::parse($filters['date_from'])->startOfDay()
      : Carbon::now()->subDays(29)->startOfDay();

    $to = isset($filters['date_to'])
      ? Carbon::parse($filters['date_to'])->endOfDay()
      : Carbon::now()->endOfDay();

    return [$from, $to];
  }

  private function baseQuery(Carbon $from, Carbon $to, array $filters)
  {
    return AuditLogs::whereIn('audit_logs.action', array_keys(self::ACTION_POINTS))
      ->whereBetween('audit_logs.created_at', [$from, $to])
      ->when(isset($filters['user_id']), fn($q) => $q->where('audit_logs.user_id', $filters['user_id']))
      ->when(isset($filters['module']), function ($q) use ($filters) {
        $q->where('audit_logs.auditable_type', 'like', '%\\' . $filters['module'] . '\\%');
      });
  }

  private function rawUserStats(Carbon $from, Carbon $to, array $filters)
  {
    return DB::table('audit_logs as al')
      ->select(
        'al.user_id',
        DB::raw('MAX(al.user_name) as user_name'),
        DB::raw('w.sede_id'),
        DB::raw('MAX(s.localidad) as sede_name'),
        DB::raw('COUNT(*) as total_ops'),
        DB::raw('SUM(al.action = \'created\') as creates'),
        DB::raw('SUM(al.action = \'updated\') as updates'),
        DB::raw('SUM(al.action = \'deleted\') as deletes'),
        DB::raw('SUM(al.action = \'restored\') as restores'),
        DB::raw('SUM(
                    CASE al.action
                        WHEN \'created\'  THEN 5
                        WHEN \'updated\'  THEN 2
                        WHEN \'deleted\'  THEN 1
                        WHEN \'restored\' THEN 3
                        ELSE 0
                    END
                ) as action_score'),
        DB::raw('COUNT(DISTINCT DATE(al.created_at)) as active_days'),
        DB::raw('COUNT(DISTINCT al.auditable_type) as unique_modules'),
        DB::raw('GROUP_CONCAT(DISTINCT al.auditable_type SEPARATOR \'|\') as modules_raw')
      )
      ->leftJoin('usr_users as u', 'al.user_id', '=', 'u.id')
      ->leftJoin('rrhh_persona as w', 'u.partner_id', '=', 'w.id')
      ->leftJoin('config_sede as s', 'w.sede_id', '=', 's.id')
      ->whereIn('al.action', array_keys(self::ACTION_POINTS))
      ->whereBetween('al.created_at', [$from, $to])
      ->when(isset($filters['user_id']), fn($q) => $q->where('al.user_id', $filters['user_id']))
      ->when(isset($filters['sede_id']), fn($q) => $q->where('s.id', $filters['sede_id']))
      ->when(isset($filters['module']), function ($q) use ($filters) {
        $q->where('al.auditable_type', 'like', '%\\' . $filters['module'] . '\\%');
      })
      ->groupBy('al.user_id', 'w.sede_id')
      ->get();
  }

  private function computeUserScore(object $row, int $periodDays, int $totalModules): array
  {
    $freqScore = min($row->active_days / $periodDays, 1) * 100;
    $persScore = min($row->action_score / max($periodDays * self::DAILY_TARGET_POINTS, 1), 1) * 100;
    $compScore = min($row->total_ops / max($periodDays * self::DAILY_TARGET_OPS, 1), 1) * 100;
    $qualScore = min($row->unique_modules / max($totalModules, self::TARGET_MODULES), 1) * 100;

    $total = ($freqScore * self::WEIGHTS['frequency'])
      + ($persScore * self::WEIGHTS['persistence'])
      + ($compScore * self::WEIGHTS['compliance'])
      + ($qualScore * self::WEIGHTS['quality']);

    return [
      'total' => $total,
      'breakdown' => [
        'frequency' => round($freqScore, 1),
        'persistence' => round($persScore, 1),
        'compliance' => round($compScore, 1),
        'quality' => round($qualScore, 1),
      ],
    ];
  }

  private function computeSedeScore(object $row, int $periodDays, int $expected, int $totalModules): float
  {
    $freqScore = min($row->active_days / $periodDays, 1) * 100;
    $persScore = min($row->action_score / max($periodDays * self::DAILY_TARGET_POINTS * max($row->active_users, 1), 1), 1) * 100;
    $compScore = min($row->active_users / max($expected, 1), 1) * 100;
    $qualScore = min($row->unique_models / max($totalModules, self::TARGET_MODULES), 1) * 100;

    return ($freqScore * self::WEIGHTS['frequency'])
      + ($persScore * self::WEIGHTS['persistence'])
      + ($compScore * self::WEIGHTS['compliance'])
      + ($qualScore * self::WEIGHTS['quality']);
  }

  private function computeGlobalScore(Carbon $from, Carbon $to, array $filters): float
  {
    $periodDays = max(1, $from->diffInDays($to) + 1);
    $totalModules = $this->countDistinctModules($from, $to, $filters);
    $rows = $this->rawUserStats($from, $to, $filters);

    if ($rows->isEmpty()) {
      return 0;
    }

    $totalScore = $rows->sum(fn($r) => $this->computeUserScore($r, $periodDays, $totalModules)['total']);
    return $totalScore / $rows->count();
  }

  private function countDistinctModules(Carbon $from, Carbon $to, array $filters): int
  {
    $count = $this->baseQuery($from, $to, $filters)
      ->distinct('auditable_type')
      ->count('auditable_type');
    return max($count, self::TARGET_MODULES);
  }

  private function semaphore(float $pct): string
  {
    if ($pct >= 70) return 'green';
    if ($pct >= 40) return 'yellow';
    return 'red';
  }

  private function assignBadge(float $score, int $modules, int $activeDays, int $periodDays): ?string
  {
    if ($score >= self::CHAMPION_THRESHOLD) {
      return 'Campeón del Sistema';
    }
    if ($score >= self::POWERUSER_THRESHOLD && $modules >= 3) {
      return 'Power User';
    }
    if ($activeDays >= $periodDays * 0.8 && $score >= 60) {
      return 'Early Adopter';
    }
    return null;
  }

  private function extractModuleLabel(string $auditableType): string
  {
    // App\Models\{top}\{sub}\{Model}
    $parts = explode('\\', $auditableType);

    if (count($parts) < 4) {
      return 'General';
    }

    $top = strtolower($parts[2] ?? '');
    $sub = $parts[3] ?? '';

    return self::MODULE_LABELS[$top][$sub]
      ?? self::MODULE_LABELS[$top][strtolower($sub)]
      ?? ucfirst($top) . ' - ' . $sub;
  }

  private function decodeModules(string $raw): array
  {
    if (!$raw) return [];
    return collect(explode('|', $raw))
      ->map(fn($t) => $this->extractModuleLabel($t))
      ->unique()
      ->values()
      ->all();
  }

  private function trendSummary(array $filters, int $days): array
  {
    $to = Carbon::now()->endOfDay();
    $from = Carbon::now()->subDays($days - 1)->startOfDay();
    $ops = AuditLogs::whereIn('action', array_keys(self::ACTION_POINTS))
      ->whereBetween('created_at', [$from, $to])
      ->when(isset($filters['sede_id']), function ($q) use ($filters) {
        $q->whereHas('user.person.sede', fn($sq) => $sq->where('id', $filters['sede_id']));
      })
      ->count();
    $users = AuditLogs::whereIn('action', array_keys(self::ACTION_POINTS))
      ->whereBetween('created_at', [$from, $to])
      ->distinct('user_id')->count('user_id');

    return ['ops' => $ops, 'active_users' => $users, 'days' => $days];
  }
}
