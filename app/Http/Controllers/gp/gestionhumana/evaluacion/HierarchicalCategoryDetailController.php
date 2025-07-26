<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexHierarchicalCategoryDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreHierarchicalCategoryDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateHierarchicalCategoryDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\HierarchicalCategoryDetailService;

class HierarchicalCategoryDetailController extends Controller
{
    protected HierarchicalCategoryDetailService $service;

    public function __construct(HierarchicalCategoryDetailService $service)
    {
        $this->service = $service;
    }

    public function index(IndexHierarchicalCategoryDetailRequest $request)
    {

    }

    public function store(StoreHierarchicalCategoryDetailRequest $request)
    {
        //
    }

    public function storeMany(StoreHierarchicalCategoryDetailRequest $request, int $categoryId)
    {
        try {
            $positions = $request->validated()['positions'];
            return $this->success($this->service->storeMany($categoryId, $positions));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show($id)
    {
        //
    }

    public function update(UpdateHierarchicalCategoryDetailRequest $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
