<?php

namespace App\Http\Controllers;

use App\Models\EvaluationPeriod;
use App\Http\Requests\StoreEvaluationPeriodRequest;
use App\Http\Requests\UpdateEvaluationPeriodRequest;

class EvaluationPeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreEvaluationPeriodRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EvaluationPeriod $evaluationPeriod)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EvaluationPeriod $evaluationPeriod)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEvaluationPeriodRequest $request, EvaluationPeriod $evaluationPeriod)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EvaluationPeriod $evaluationPeriod)
    {
        //
    }
}
