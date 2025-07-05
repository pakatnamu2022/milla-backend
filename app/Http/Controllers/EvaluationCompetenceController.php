<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexEvaluationCompetenceRequest;
use App\Http\Services\EvaluationCompetenceService;
use App\Models\EvaluationCompetence;
use App\Http\Requests\StoreEvaluationCompetenceRequest;
use App\Http\Requests\UpdateEvaluationCompetenceRequest;

class EvaluationCompetenceController extends Controller
{
    protected EvaluationCompetenceService $evaluationCompetenceService;

    public function __construct(EvaluationCompetenceService $evaluationCompetenceService)
    {
        $this->evaluationCompetenceService = $evaluationCompetenceService;
    }

    public function index(IndexEvaluationCompetenceRequest $request)
    {
        return $this->evaluationCompetenceService->list($request);
    }

    public function store(StoreEvaluationCompetenceRequest $request)
    {
        $data = $request->validated();
        return response()->json($this->evaluationCompetenceService->store($data));
    }

    public function show(int $id)
    {
        try {
            return response()->json($this->evaluationCompetenceService->show($id));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateEvaluationCompetenceRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id; // Add the ID to the data for updating
            return response()->json($this->evaluationCompetenceService->update($data));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(int $id)
    {
        try {
            return $this->evaluationCompetenceService->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
