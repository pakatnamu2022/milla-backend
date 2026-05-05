<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Services\Dashboard\AdoptionDashboardService;
use Illuminate\Http\Request;

class AdoptionDashboardController extends Controller
{
    protected AdoptionDashboardService $service;

    public function __construct(AdoptionDashboardService $service)
    {
        $this->service = $service;
    }

    /**
     * KPIs ejecutivos: usuarios activos, ops totales, top sede, índice global, tendencia.
     */
    public function summary(Request $request)
    {
        try {
            $data = $this->service->getExecutiveSummary($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Ranking de usuarios con score de adopción y badges.
     */
    public function users(Request $request)
    {
        try {
            $data = $this->service->getUserRanking($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Ranking de sedes con índice de adopción y semáforo.
     */
    public function sedes(Request $request)
    {
        try {
            $data = $this->service->getSedeRanking($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Uso por módulos: volumen, share %, usuarios, modelos detalle.
     */
    public function modules(Request $request)
    {
        try {
            $data = $this->service->getModuleUsage($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Cumplimiento esperado vs real, semáforo por usuario.
     */
    public function compliance(Request $request)
    {
        try {
            $data = $this->service->getCompliance($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Usuarios campeones (con badges) y usuarios en riesgo de no adopción.
     */
    public function champions(Request $request)
    {
        try {
            $data = $this->service->getChampionsAndAtRisk($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Alertas automáticas: inactivos, sedes rezagadas, módulos subutilizados, caída de actividad.
     */
    public function alerts(Request $request)
    {
        try {
            $data = $this->service->getAlerts($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Evolución diaria de operaciones y usuarios activos.
     */
    public function trend(Request $request)
    {
        try {
            $data = $this->service->getTrend($this->filters($request));
            return $this->success($data);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    // -------------------------------------------------------------------------

    private function filters(Request $request): array
    {
        return $request->only([
            'date_from',
            'date_to',
            'sede_id',
            'user_id',
            'module',
            'role_id',
            'expected_ops_per_day',
        ]);
    }
}