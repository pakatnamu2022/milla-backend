<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexHierarchicalCategoryRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\HierarchicalCategoryService;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Illuminate\Http\Request;

class HierarchicalCategoryController extends Controller
{

    protected HierarchicalCategoryService $service;

    public function __construct(HierarchicalCategoryService $service)
    {
        $this->service = $service;
    }

    public function index(IndexHierarchicalCategoryRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(Request $request)
    {
        //
    }

    public function show(HierarchicalCategory $hierarchicalCategory)
    {
        //
    }

    public function update(Request $request, HierarchicalCategory $hierarchicalCategory)
    {
        //
    }

    public function destroy(HierarchicalCategory $hierarchicalCategory)
    {
        //
    }
}
