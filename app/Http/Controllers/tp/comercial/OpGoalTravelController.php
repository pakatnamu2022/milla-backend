<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\tp\comercial\StoreOpGoalTravelRequest;
use App\Http\Requests\tp\comercial\UpdateOpGoalTravelRequest;
use App\Http\Services\tp\comercial\OpGoalTravelService;
use App\Http\Services\tp\comercial\PrediccionCumplimientoService;
use App\Models\tp\comercial\OpGoalTravel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpGoalTravelController extends Controller
{
    protected OpGoalTravelService $service;

    public function __construct(OpGoalTravelService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try{
            return response()->json($this->service->list($request));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOpGoalTravelRequest $request)
    {
        try{
            $data = $request->validated();
            return response()->json($this->service->store($data), 201);

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
         try{
            return response()->json($this->service->show($id));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OpGoalTravel $opGoalTravel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOpGoalTravelRequest $request, $id)
    {
        try{
            $data = $request->validated();
            $data['id'] = $id;
            return response()->json($this->service->update($data));

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try{
            return response()->json($this->service->destroy($id));
        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    public function comparativaMensual(Request $request)
    {
        try{
            $year1 = $request->input('year1', date('Y'));
            $month1 = $request->input('month1', date('m'));
            $year2 = $request->input('year2', null);
            $month2 = $request->input('month2', null);

            $fecha1 = \Carbon\Carbon::create($year1, $month1, 1);
            if ($fecha1->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El período principal no puede ser una fecha futura.'
                ], 400);
            }
            $data = $this->service->getComparativaMensual(
                (int)$year1, 
                (int)$month1, 
                $year2 !== null ? (int)$year2 : null, 
                $month2 !== null ? (int)$month2 : null
            );

            return response()->json($data);

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }

    }

    public function viajesNoFacturados(Request $request){
        try{
            $dias = $request->input('dias', 4);
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', null); 
            $data = $this->service->getViajesNoFacturados((int)$dias, (int)$year, $month ? (int)$month : null);
            return response()->json($data);

        }catch(Throwable $th){
            return $this->error($th->getMessage());
        }
    }

    public function dashboard(Request $request)
    {
        try{
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));

            $data = $this->service->getDashboardData((int)$year, (int)$month);

            return response()->json($data);

        }catch(Throwable $th){
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function ranking(Request $request)
    {
        try {
            $periodo = $request->input('periodo', 'month');
            $limit = $request->input('limit', 10);
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));
            
            $data = $this->service->getRanking($periodo, $limit, $year, $month);
            return response()->json($data);

        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function alerts(Request $request)
    {
        try {
            $threshold = $request->input('threshold', 70);
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));
            $data = $this->service->getAlerts((int)$threshold, (int)$year, (int)$month);
            return response()->json($data);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function availableYears()
    {
        try {
            return response()->json($this->service->getAvailableYears());
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function analisisEstrategico(Request $request)
    {
        try {
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');
            $data = $this->service->getAnalisisEstrategico($fechaInicio, $fechaFin);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'tendencia' => [],
                'top_crecimiento' => [],
                'top_decrecimiento' => [],
                'clientes_nuevos' => [],
                'clientes_inactivos' => [],
                'proyeccion' => [
                    'acumulado' => 0,
                    'promedio_diario' => 0,
                    'proyeccion' => 0,
                    'meta' => 0,
                    'cumplimiento' => 0,
                    'dias_transcurridos' => 0,
                    'dias_totales' => 0,
                    'periodo' => ''
                ],
                'distribucion' => []
            ], 200);
        }
    }

    public function predecirCumplimiento(Request $request)
    {
        $request->validate([
            'meses_historicos' => 'required|integer|min:3|max:36',
            'factor_confianza' => 'sometimes|numeric|min:0.8|max:1.2'
        ]);
        
        try {
            $service = new PrediccionCumplimientoService();
            $resultado = $service->predecirCumplimiento(
                $request->input('meses_historicos', 12),
                $request->input('factor_confianza', 1.0)
            );
            
            return response()->json($resultado);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar predicción: ' . $e->getMessage()
            ], 500);
        }
    }

    // OpGoalTravelController.php - Agregar este método

public function exportComparativaClientes(Request $request)
{
    try {
        $year1 = $request->input('year1', date('Y'));
        $month1 = $request->input('month1', date('m'));
        $year2 = $request->input('year2', null);
        $month2 = $request->input('month2', null);

        // Validar fechas
        $fecha1 = \Carbon\Carbon::create($year1, $month1, 1);
        if ($fecha1->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'El período principal no puede ser una fecha futura.'
            ], 400);
        }

        return $this->service->exportComparativaClientes(
            (int)$year1,
            (int)$month1,
            $year2 !== null ? (int)$year2 : null,
            $month2 !== null ? (int)$month2 : null
        );

    } catch (Throwable $th) {
        return $this->error($th->getMessage());
    }
}

}
