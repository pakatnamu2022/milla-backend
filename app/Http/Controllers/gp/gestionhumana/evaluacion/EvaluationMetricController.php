<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationMetricRequest;
use App\Http\Requests\UpdateEvaluationMetricRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationMetricService;
use Illuminate\Http\Request;

class EvaluationMetricController extends Controller
{
    protected EvaluationMetricService $service;

    public function __construct(EvaluationMetricService $service)
    {
        $this->service = $service;
    }


    public function index(Request $request)
    {
        return $this->service->list($request);
    }

    public function store(StoreEvaluationMetricRequest $request)
    {
        $data = $request->validated();
        return response()->json($this->service->store($data));
    }

    public function show(int $id)
    {
        try {
            return response()->json($this->service->show($id));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateEvaluationMetricRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id; // Add the ID to the data for updating
            return response()->json($this->service->update($data));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            return $this->service->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
