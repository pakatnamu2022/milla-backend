<?php

namespace App\Http\Controllers;

use App\Http\Services\EquipmentService;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    protected EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->equipmentService->list($request);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Logic to show form for creating new equipment
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Logic to store new equipment
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Logic to show specific equipment
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Logic to show form for editing specific equipment
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Logic to update specific equipment
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Logic to delete specific equipment
    }
}
