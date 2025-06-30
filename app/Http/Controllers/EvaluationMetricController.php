<?php

namespace App\Http\Controllers;

use App\Http\Services\EvaluationMetricService;
use App\Http\Requests\StoreEvaluationMetricRequest;
use App\Http\Requests\UpdateEvaluationMetricRequest;
use Illuminate\Http\Request;

class EvaluationMetricController extends Controller
{
    protected EvaluationMetricService $evaluationMetricService;

    public function __construct(EvaluationMetricService $evaluationMetricService)
    {
        $this->evaluationMetricService = $evaluationMetricService;
    }


    public function index(Request $request)
    {
        return $this->evaluationMetricService->list($request);
    }

    public function store(StoreEvaluationMetricRequest $request)
    {
        $data = $request->validated();
        return response()->json($this->evaluationMetricService->store($data));
    }

    public function show(int $id)
    {
        try {
            return response()->json($this->evaluationMetricService->show($id));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateEvaluationMetricRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id; // Add the ID to the data for updating
            return response()->json($this->evaluationMetricService->update($data));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            return $this->evaluationMetricService->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
